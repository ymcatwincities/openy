<?php

namespace Drupal\openy\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a form for setting Search Solr server parameters during install.
 */
class SearchSolrForm extends FormBase {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'openy_solr_search';
  }

  /**
   * Constructs a new GroupexFormBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $solr_server_config = $this->configFactory->get('search_api.server.solr_search');
    $solr_connector_options = [
      'standard' => 'Standard',
      'basic_auth' => 'Basic Auth',
    ];
    $form['#title'] = $this->t('Configure Solr server');
    $form['connector'] = [
      '#type' => 'radios',
      '#title' => $this->t('Solr Connector'),
      '#description' => $this->t('Choose a connector to use for this Solr server.'),
      '#options' => $solr_connector_options,
      '#default_value' => $solr_server_config->get('backend_config.connector'),
      '#required' => TRUE,
    ];
    $form['connector_set'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Configure Solr Connector'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    ];

    $form['connector_set']['scheme'] = [
      '#type' => 'select',
      '#title' => $this->t('HTTP protocol'),
      '#description' => $this->t('The HTTP protocol to use for sending queries.'),
      '#default_value' => $solr_server_config->get('backend_config.connector_config.scheme'),
      '#options' => [
        'http' => 'http',
        'https' => 'https',
      ],
    ];

    $form['connector_set']['host'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Solr host'),
      '#description' => $this->t('The host name or IP of your Solr server, e.g. <code>localhost</code> or <code>www.example.com</code>.'),
      '#default_value' => $solr_server_config->get('backend_config.connector_config.host'),
      '#required' => TRUE,
    ];

    $form['connector_set']['port'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Solr port'),
      '#description' => $this->t('The Jetty example server is at port 8983, while Tomcat uses 8080 by default.'),
      '#default_value' => $solr_server_config->get('backend_config.connector_config.port'),
      '#required' => TRUE,
    ];

    $form['connector_set']['path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Solr path'),
      '#description' => $this->t('The path that identifies the Solr instance to use on the server.'),
      '#default_value' => $solr_server_config->get('backend_config.connector_config.path'),
    ];

    $form['connector_set']['core'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Solr core'),
      '#description' => $this->t('The name that identifies the Solr core to use on the server.'),
      '#default_value' => $solr_server_config->get('backend_config.connector_config.core'),
    ];

    $form['connector_set']['timeout'] = [
      '#type' => 'number',
      '#min' => 1,
      '#max' => 180,
      '#title' => $this->t('Query timeout'),
      '#description' => $this->t('The timeout in seconds for search queries sent to the Solr server.'),
      '#default_value' => $solr_server_config->get('backend_config.connector_config.timeout'),
      '#required' => TRUE,
    ];

    $form['connector_set']['index_timeout'] = [
      '#type' => 'number',
      '#min' => 1,
      '#max' => 180,
      '#title' => $this->t('Index timeout'),
      '#description' => $this->t('The timeout in seconds for indexing requests to the Solr server.'),
      '#default_value' => $solr_server_config->get('backend_config.connector_config.index_timeout'),
      '#required' => TRUE,
    ];

    $form['connector_set']['optimize_timeout'] = [
      '#type' => 'number',
      '#min' => 1,
      '#max' => 180,
      '#title' => $this->t('Optimize timeout'),
      '#description' => $this->t('The timeout in seconds for background index optimization queries on a Solr server.'),
      '#default_value' => $solr_server_config->get('backend_config.connector_config.optimize_timeout'),
      '#required' => TRUE,
    ];

    $form['connector_set']['commit_within'] = [
      '#type' => 'number',
      '#min' => 0,
      '#title' => $this->t('Commit within'),
      '#description' => $this->t('The limit in milliseconds within a (soft) commit on Solr is forced after any updating the index in any way. Setting the value to "0" turns off this dynamic enforcement and lets Solr behave like configured solrconf.xml.'),
      '#default_value' => $solr_server_config->get('backend_config.connector_config.commit_within'),
      '#required' => TRUE,
    ];

    $form['auth'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('HTTP Basic Authentication'),
      '#description' => $this->t('If your Solr server is protected by basic HTTP authentication, enter the login data here.'),
      '#collapsible' => FALSE,
      '#states' => [
        'visible' => [
          ':input[name="connector"]' => ['value' => 'basic_auth'],
        ],
      ],
    ];

    $form['auth']['username'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Username'),
      '#default_value' => $solr_server_config->get('backend_config.connector_config.username'),
    ];

    $form['auth']['password'] = [
      '#type' => 'password',
      '#title' => $this->t('Password'),
      '#description' => $this->t('If this field is left blank and the HTTP username is filled out, the current password will not be changed.'),
    ];

    $form['actions'] = [
      'continue' => [
        '#type' => 'submit',
        '#value' => $this->t('Continue'),
      ],
      '#type' => 'actions',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    if (isset($values['port']) && (!is_numeric($values['port']) || $values['port'] < 0 || $values['port'] > 65535)) {
      $form_state->setError($form['connector_set']['port'], $this->t('The port has to be an integer between 0 and 65535.'));
    }
    if (!empty($values['path']) && strpos($values['path'], '/') !== 0) {
      $form_state->setError($form['connector_set']['path'], $this->t('If provided the path has to start with "/".'));
    }
    if (!empty($values['core']) && strpos($values['core'], '/') === 0) {
      $form_state->setError($form['connector_set']['core'], $this->t('The core must not start with "/".'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    // Save solr server configuration.
    $solr_server_config = $this->configFactory->getEditable('search_api.server.solr_search');
    $solr_server_config->set('backend_config.connector', $values['connector']);
    $solr_server_config->set('backend_config.connector_config.scheme', $values['scheme']);
    $solr_server_config->set('backend_config.connector_config.host', $values['host']);
    $solr_server_config->set('backend_config.connector_config.port', $values['port']);
    $solr_server_config->set('backend_config.connector_config.path', $values['path']);
    $solr_server_config->set('backend_config.connector_config.core', $values['core']);
    $solr_server_config->set('backend_config.connector_config.timeout', $values['timeout']);
    $solr_server_config->set('backend_config.connector_config.index_timeout', $values['index_timeout']);
    $solr_server_config->set('backend_config.connector_config.optimize_timeout', $values['optimize_timeout']);
    $solr_server_config->set('backend_config.connector_config.commit_within', $values['commit_within']);
    // For basic_auth also save username and password.
    if ('basic_auth' == $values['connector']) {
      // Ignore empty password submissions if the user didn't change it.
      if (($values['password'] === '')
        && $solr_server_config->get('backend_config.connector_config.username') !== ''
        && $values['username'] === $solr_server_config->get('backend_config.connector_config.username')) {
        $values['password'] = $solr_server_config->get('backend_config.connector_config.password');
      }
      $solr_server_config->set('backend_config.connector_config.username', $values['username']);
      $solr_server_config->set('backend_config.connector_config.password', $values['password']);
    }
    $solr_server_config->save();
  }

}
