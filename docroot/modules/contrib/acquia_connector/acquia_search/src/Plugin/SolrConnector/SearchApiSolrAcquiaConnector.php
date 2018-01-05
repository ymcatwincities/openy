<?php

/**
 * @file
 * Extends SolrConnectorPluginBase for acquia search.
 */

namespace Drupal\acquia_search\Plugin\SolrConnector;

use Drupal\acquia_connector\Helper\Storage;
use Drupal\Core\Url;
use Drupal\search_api_solr\SolrConnector\SolrConnectorPluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\acquia_search\EventSubscriber\SearchSubscriber;
use Solarium\Core\Client\Client;

/**
 * Class SearchApiSolrAcquiaConnector.
 *
 * @package Drupal\acquia_search\Plugin\SolrConnector
 *
 * @SolrConnector(
 *   id = "solr_acquia_connector",
 *   label = @Translation("Acquia"),
 *   description = @Translation("Index items using an Acquia Apache Solr search server.")
 * )
 */
class SearchApiSolrAcquiaConnector extends SolrConnectorPluginBase {

  protected $eventDispatcher = FALSE;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $configuration = parent::defaultConfiguration();
    $storage = new Storage();
    $configuration['index_id'] = $storage->getIdentifier();
    $configuration['path'] = '/solr/' . $storage->getIdentifier();
    $configuration['host'] = acquia_search_get_search_host();
    $configuration['port'] = '80';
    $configuration['scheme'] = 'http';

    unset($configuration['overridden_by_acquia_search']);

    // If auto-switch feature is turned off - do not attempt to determine the
    // preferred core.
    if (acquia_search_is_auto_switch_disabled()) {
      return $configuration;
    }

    // If the search config is overridden in settings.php, apply this config
    // to the Solr connection and don't attempt to determine the preferred
    // core.
    if (acquia_search_is_connection_config_overridden()) {
      $configuration = $this->setOverriddenCore($configuration);
      return $configuration;
    }

    $preferred_core_service = acquia_search_get_core_service();

    // If the preferred core available, set it.
    if ($preferred_core_service->isPreferredCoreAvailable()) {
      $configuration = $this->setPreferredCore($configuration, $preferred_core_service);
    }
    else {
      // This means we can't detect which search core should be used, so we
      // need to protect it by setting read-only mode but only if it applies.
      if (acquia_search_should_set_read_only_mode()) {
        $configuration = $this->setReadOnlyMode($configuration);
      }
    }

    return $configuration;
  }

  /**
   * Sets the preferred core in the given Solr config.
   *
   * @param $configuration
   *   Solr connection configuration.
   *
   * @param \Drupal\acquia_search\PreferredSearchCoreService $preferred_core_service
   *   Service for determining the preferred search core.
   *
   * @return array
   *   Updated Solr connection configuration.
   */
  protected function setPreferredCore($configuration, $preferred_core_service) {
    $configuration['index_id'] = $preferred_core_service->getPreferredCoreId();
    $configuration['path'] = '/solr/' . $preferred_core_service->getPreferredCoreId();
    $configuration['host'] = $preferred_core_service->getPreferredCoreHostname();
    $configuration['overridden_by_acquia_search'] = ACQUIA_SEARCH_OVERRIDE_AUTO_SET;
    return $configuration;
  }

  /**
   * Sets the current connection overrides to the given Solr config.
   *
   * @param $configuration
   *   Solr connection configuration.
   *
   * @return array
   *   Updated Solr connection configuration.
   */
  protected function setOverriddenCore($configuration) {
    $override = \Drupal::config('acquia_search.settings')->get('connection_override');
    $configuration['overridden_by_acquia_search'] = ACQUIA_SEARCH_EXISTING_OVERRIDE;
    $configuration['path'] = '/solr/' . $override['index_id'];
    return array_merge($configuration, $override);
  }

  /**
   * Sets read-only mode to the given Solr config.
   *
   * We enforce read-only mode in 2 ways:
   * - The module implements hook_search_api_index_load() and alters indexes'
   * read-only flag.
   * - In this plugin, we "emulate" read-only mode by overriding
   * $this->getUpdateQuery() and avoiding all updates just in case something
   * is still attempting to directly call a Solr update.
   *
   * @param $configuration
   *   Solr connection configuration.
   *
   * @return array
   *   Updated Solr connection configuration.
   */
  protected function setReadOnlyMode($configuration) {
    $configuration['overridden_by_acquia_search'] = ACQUIA_SEARCH_AUTO_OVERRIDE_READ_ONLY;
    return $configuration;
  }

  /**
   * {@inheritdoc}
   *
   * Acquia-specific: 'admin/info/system' path is protected by Acquia.
   * Use admin/system instead.
   */
  public function pingServer() {
    return $this->doPing(['handler' => 'admin/system'], 'server');
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    unset($form['host']);
    unset($form['port']);
    unset($form['path']);
    unset($form['core']);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Turn off connection check of parent class.
  }

  /**
   * {@inheritdoc}
   */
  protected function connect() {
    if (!$this->solr) {
      $this->solr = new Client();
      $this->configuration['port'] = ($this->configuration['scheme'] == 'https') ? 443 : 80;
      $this->configuration['key'] = 'core';
      $this->solr->createEndpoint($this->configuration, TRUE);
      $this->attachServerEndpoint();
      $this->eventDispatcher = $this->solr->getEventDispatcher();
      $plugin = new SearchSubscriber();
      $this->solr->registerPlugin('acquia_solr_search_subscriber', $plugin);
      // Don't use curl.
      $this->solr->setAdapter('Solarium\Core\Client\Adapter\Http');
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getServerUri() {
    $this->connect();
    return $this->solr->getEndpoint('core')->getBaseUri();
  }

  /**
   * {@inheritdoc}
   *
   * Avoid providing an valid Update query if module determines this server
   * should be locked down (as indicated by the overridden_by_acquia_search
   * server option).
   */
  public function getUpdateQuery() {
    $this->connect();
    $overridden = $this->solr->getEndpoint('server')->getOption('overridden_by_acquia_search');
    if ($overridden === ACQUIA_SEARCH_AUTO_OVERRIDE_READ_ONLY) {
      $message = 'The Search API Server serving this index is currently in read-only mode.';
      \Drupal::logger('acquia search')->error($message);
      throw new \Exception($message);
    }
    return $this->solr->createUpdate();
  }

  /**
   * {@inheritdoc}
   */
  public function getExtractQuery() {
    $this->connect();
    $query = $this->solr->createExtract();
    $query->setHandler('extract/tika');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function getMoreLikeThisQuery() {
    $this->connect();
    $query = $this->solr->createMoreLikeThis();
    $query->setHandler('select');
    $query->addParam('qt', 'mlt');
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function getCoreLink() {
    return $this->getServerLink();
  }

  /**
   * {@inheritdoc}
   */
  public function viewSettings() {
    $uri = Url::fromUri('http://www.acquia.com/products-services/acquia-search', array('absolute' => TRUE));
    drupal_set_message(t("Search is being provided by @as.", array('@as' => \Drupal::l(t('Acquia Search'), $uri))));
    return parent::viewSettings();
  }

}
