<?php

/**
 * @file
 * Contains \Drupal\search_api\Plugin\views\row\SearchApiRow.
 */

namespace Drupal\search_api\Plugin\views\row;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\search_api\Plugin\views\query\SearchApiQuery;
use Drupal\search_api\SearchApiException;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\row\RowPluginBase;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a row plugin for displaying a result as a rendered item.
 *
 * @ViewsRow(
 *   id = "search_api",
 *   title = @Translation("Rendered Search API item"),
 *   help = @Translation("Displays entity of the matching search API item"),
 * )
 */
class SearchApiRow extends RowPluginBase {

  /**
   * The search index.
   *
   * @var \Drupal\search_api\IndexInterface
   */
  protected $index;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The logger to use for logging messages.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface|null
   */
  // @todo Make this into a trait, with an additional logException() method.
  protected $logger;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var static $row */
    $row = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    /** @var \Drupal\Core\Entity\EntityManagerInterface $entity_manager */
    $entity_manager = $container->get('entity.manager');
    $row->setEntityManager($entity_manager);

    /** @var \Drupal\Core\Logger\LoggerChannelInterface $logger */
    $logger = $container->get('logger.factory')->get('search_api');
    $row->setLogger($logger);

    return $row;
  }

  /**
   * Retrieves the entity manager.
   *
   * @return \Drupal\Core\Entity\EntityManagerInterface
   *   The entity manager.
   */
  public function getEntityManager() {
    return $this->entityManager ?: \Drupal::entityManager();
  }

  /**
   * Sets the entity manager.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entityManager
   *   The new entity manager.
   *
   * @return $this
   */
  public function setEntityManager(EntityManagerInterface $entityManager) {
    $this->entityManager = $entityManager;
    return $this;
  }

  /**
   * Retrieves the logger to use.
   *
   * @return \Drupal\Core\Logger\LoggerChannelInterface
   *   The logger to use.
   */
  public function getLogger() {
    return $this->logger ? : \Drupal::service('logger.factory')->get('search_api');
  }

  /**
   * Sets the logger to use.
   *
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger to use.
   */
  public function setLogger(LoggerChannelInterface $logger) {
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    $base_table = $view->storage->get('base_table');
    $this->index = SearchApiQuery::getIndexFromTable($base_table, $this->getEntityManager());
    if (!$this->index) {
      throw new \InvalidArgumentException(new FormattableMarkup('View %view is not based on Search API but tries to use its row plugin.', array('%view' => $view->storage->label())));
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['view_modes'] = array('default' => array());

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    /** @var \Drupal\search_api\Datasource\DatasourceInterface $datasource */
    foreach ($this->index->getDatasources() as $datasource_id => $datasource) {
      $datasource_label = $datasource->label();
      $bundles = $datasource->getBundles();
      if (!$datasource->getViewModes()) {
        $form['view_modes'][$datasource_id] = array(
          '#type' => 'item',
          '#title' => $this->t('Default View mode for datasource %name', array('%name' => $datasource_label)),
          '#description' => $this->t("This datasource doesn't have any view modes available. It is therefore not possible to display results of this datasource using this row plugin."),
        );
        continue;
      }

      foreach ($bundles as $bundle_id => $bundle_label) {
        $title = $this->t('View mode for datasource %datasource, bundle %bundle', array('%datasource' => $datasource_label, '%bundle' => $bundle_label));
        $view_modes = $datasource->getViewModes($bundle_id);
        if (!$view_modes) {
          $form['view_modes'][$datasource_id][$bundle_id] = array(
            '#type' => 'item',
            '#title' => $title,
            '#description' => $this->t("This bundle doesn't have any view modes available. It is therefore not possible to display results of this bundle using this row plugin."),
          );
          continue;
        }
        $form['view_modes'][$datasource_id][$bundle_id] = array(
          '#type' => 'select',
          '#options' => $view_modes,
          '#title' => $title,
          '#default_value' => key($view_modes),
        );
        if (isset($this->options['view_modes'][$datasource_id][$bundle_id])) {
          $form['view_modes'][$datasource_id][$bundle_id]['#default_value'] = $this->options['view_modes'][$datasource_id][$bundle_id];
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function summaryTitle() {
    $summary = array();
    foreach ($this->options['view_modes'] as $datasource_id => $bundles) {
      $datasource = $this->index->getDatasource($datasource_id);
      $bundles_info = $datasource->getBundles();
      foreach ($bundles as $bundle => $view_mode) {
        $view_modes = $datasource->getViewModes($bundle);
        $args = array(
          '@bundle' => $bundles_info[$bundle],
          '@datasource' => $datasource->label(),
          '@view_mode' => $view_modes[$view_mode],
        );
        $summary[] = $this->t('@datasource/@bundle: @view_mode', $args);
      }
    }
    return $summary ? implode('; ', $summary) : $this->t('No settings');
  }

  /**
   * {@inheritdoc}
   */
  public function render($row) {
    $datasource_id = $row->search_api_datasource;

    if (!($row->_item instanceof ComplexDataInterface)) {
      $context = array(
        '%item_id' => $row->search_api_id,
        '%view' => $this->view->storage->label(),
      );
      $this->getLogger()->warning('Failed to load item %item_id in view %view.', $context);
      return '';
    }

    if (!$this->index->isValidDatasource($datasource_id)) {
      $context = array(
        '%datasource' => $datasource_id,
        '%view' => $this->view->storage->label(),
      );
      $this->getLogger()->warning('Item of unknown datasource %datasource returned in view %view.', $context);
      return '';
    }
    // Always use the default view mode if it was not set explicitly in the
    // options.
    $view_mode = 'default';
    $bundle = $this->index->getDatasource($datasource_id)->getItemBundle($row->_item);
    if (isset($this->options['view_modes'][$datasource_id][$bundle])) {
      $view_mode = $this->options['view_modes'][$datasource_id][$bundle];
    }

    try {
      return $this->index->getDatasource($datasource_id)->viewItem($row->_item, $view_mode);
    }
    catch (SearchApiException $e) {
      watchdog_exception('search_api', $e);
      return '';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    parent::query();
    // @todo Find a better way to ensure that the item is loaded.
    $this->view->query->addField('_magic');
  }

}
