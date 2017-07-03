<?php

namespace Drupal\migrate_tools\Controller;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\migrate_plus\Entity\MigrationGroup;
use Drupal\migrate_plus\Plugin\MigrationConfigEntityPluginManager;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Datetime\DateFormatter;

/**
 * Provides a listing of migration entities in a given group.
 *
 * @package Drupal\migrate_tools\Controller
 *
 * @ingroup migrate_tools
 */
class MigrationListBuilder extends ConfigEntityListBuilder implements EntityHandlerInterface {

  /**
   * Default object for current_route_match service.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRouteMatch;

  /**
   * Plugin manager for migration plugins.
   *
   * @var \Drupal\migrate_plus\Plugin\MigrationConfigEntityPluginManager
   */
  protected $migrationConfigEntityPluginManager;

  /**
   * Constructs a new EntityListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Routing\CurrentRouteMatch $current_route_match
   *   The current route match service.
   * @param \Drupal\migrate_plus\Plugin\MigrationConfigEntityPluginManager $migration_config_entity_plugin_manager
   *   The plugin manager for config entity-based migrations.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, CurrentRouteMatch $current_route_match, MigrationConfigEntityPluginManager $migration_config_entity_plugin_manager) {
    parent::__construct($entity_type, $storage);
    $this->currentRouteMatch = $current_route_match;
    $this->migrationConfigEntityPluginManager = $migration_config_entity_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('current_route_match'),
      $container->get('plugin.manager.config_entity_migration')
    );
  }

  /**
   * Retrieve the migrations belonging to the appropriate group.
   *
   * @return array
   *   An array of entity IDs.
   */
  protected function getEntityIds() {
    $migration_group = $this->currentRouteMatch->getParameter('migration_group');

    $query = $this->getStorage()->getQuery()
      ->sort($this->entityType->getKey('id'));

    $migration_groups = MigrationGroup::loadMultiple();

    if (array_key_exists($migration_group, $migration_groups)) {
      $query->condition('migration_group', $migration_group);
    }
    else {
      $query->notExists('migration_group');
    }
    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }
    return $query->execute();
  }

  /**
   * Builds the header row for the entity listing.
   *
   * @return array
   *   A render array structure of header strings.
   *
   * @see Drupal\Core\Entity\EntityListController::render()
   */
  public function buildHeader() {
    $header['label'] = $this->t('Migration');
    $header['machine_name'] = $this->t('Machine Name');
    $header['status'] = $this->t('Status');
    $header['total'] = $this->t('Total');
    $header['imported'] = $this->t('Imported');
    $header['unprocessed'] = $this->t('Unprocessed');
    $header['messages'] = $this->t('Messages');
    $header['last_imported'] = $this->t('Last Imported');
    return $header; // + parent::buildHeader();
  }

  /**
   * Builds a row for a migration plugin.
   *
   * @param \Drupal\Core\Entity\EntityInterface $migration_entity
   *   The migration plugin for which to build the row.
   *
   * @return array
   *   A render array of the table row for displaying the plugin information.
   *
   * @see Drupal\Core\Entity\EntityListController::render()
   */
  public function buildRow(EntityInterface $migration_entity) {
    $migration = $this->migrationConfigEntityPluginManager->createInstance($migration_entity->id());
    $migration_group = $migration->get('migration_group');
    if (!$migration_group) {
      $migration_group = 'default';
    }
    $route_parameters = array(
      'migration_group' => $migration_group,
      'migration' => $migration->id(),
    );
    $row['label'] = array(
      'data' => array(
        '#type' => 'link',
        '#title' => $migration->label(),
        '#url' => Url::fromRoute("entity.migration.overview", $route_parameters),
      ),
    );
    $row['machine_name'] = $migration->id();
    $row['status'] = $migration->getStatusLabel();

    // Derive the stats.
    $source_plugin = $migration->getSourcePlugin();
    $row['total'] = $source_plugin->count();
    $map = $migration->getIdMap();
    $row['imported'] = $map->importedCount();
    // -1 indicates uncountable sources.
    if ($row['total'] == -1) {
      $row['total'] = $this->t('N/A');
      $row['unprocessed'] = $this->t('N/A');
    }
    else {
      $row['unprocessed'] = $row['total'] - $map->processedCount();
    }
    $row['messages'] = array(
      'data' => array(
        '#type' => 'link',
        '#title' => $map->messageCount(),
        '#url' => Url::fromRoute("migrate_tools.messages", $route_parameters),
      ),
    );
    $migrate_last_imported_store = \Drupal::keyValue('migrate_last_imported');
    $last_imported =  $migrate_last_imported_store->get($migration->id(), FALSE);
    if ($last_imported) {
      /** @var DateFormatter $date_formatter */
      $date_formatter = \Drupal::service('date.formatter');
      $row['last_imported'] = $date_formatter->format($last_imported / 1000,
        'custom', 'Y-m-d H:i:s');
    }
    else {
      $row['last_imported'] = '';
    }
    return $row; // + parent::buildRow($migration_entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    $operations = parent::getDefaultOperations($entity);
    $migration_group = $entity->get('migration_group');
    if (!$migration_group) {
      $migration_group = 'default';
    }
//    $this->addGroupParameter($operations['edit']['url'], $migration_group);
//    $this->addGroupParameter($operations['delete']['url'], $migration_group);
    return $operations;
  }

  /**
   * @param \Drupal\Core\Url $url
   *   The URL associated with an operation.
   *
   * @param $migration_group
   *   The migration's parent group.
   */
  protected function addGroupParameter(Url $url, $migration_group) {
    if (!$migration_group) {
      $migration_group = 'default';
    }
    $route_parameters = $url->getRouteParameters() + ['migration_group' => $migration_group];
    $url->setRouteParameters($route_parameters);
  }

}
