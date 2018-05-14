<?php

namespace Drupal\migrate_tools\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_plus\Plugin\MigrationConfigEntityPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Returns responses for migrate_tools message routes.
 */
class MessageController extends ControllerBase {

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Plugin manager for migration plugins.
   *
   * @var \Drupal\migrate_plus\Plugin\MigrationConfigEntityPluginManager
   */
  protected $migrationConfigEntityPluginManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('plugin.manager.config_entity_migration')
    );
  }

  /**
   * Constructs a MessageController object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   A database connection.
   * @param \Drupal\migrate_plus\Plugin\MigrationConfigEntityPluginManager $migration_config_entity_plugin_manager
   *   The plugin manager for config entity-based migrations.
   */
  public function __construct(Connection $database, MigrationConfigEntityPluginManager $migration_config_entity_plugin_manager) {
    $this->database = $database;
    $this->migrationConfigEntityPluginManager = $migration_config_entity_plugin_manager;
  }

  /**
   * Gets an array of log level classes.
   *
   * @return array
   *   An array of log level classes.
   */
  public static function getLogLevelClassMap() {
    return [
      MigrationInterface::MESSAGE_INFORMATIONAL => 'migrate-message-4',
      MigrationInterface::MESSAGE_NOTICE => 'migrate-message-3',
      MigrationInterface::MESSAGE_WARNING => 'migrate-message-2',
      MigrationInterface::MESSAGE_ERROR => 'migrate-message-1',
    ];
  }

  /**
   * Displays a listing of migration messages.
   *
   * Messages are truncated at 56 chars.
   *
   * @param string $migration_group
   *   Machine name of the migration's group.
   *
   * @param string $migration
   *   Machine name of the migration.
   *
   * @return array
   *   A render array as expected by drupal_render().
   */
  public function overview($migration_group, $migration) {
    $rows = [];
    $classes = static::getLogLevelClassMap();
    /** @var MigrationInterface $migration */
    $migration = $this->migrationConfigEntityPluginManager->createInstance($migration);
    $source_id_field_names = array_keys($migration->getSourcePlugin()->getIds());
    $column_number = 1;
    foreach ($source_id_field_names as $source_id_field_name) {
      $header[] = [
        'data' => $source_id_field_name,
        'field' => 'sourceid' . $column_number++,
        'class' => [RESPONSIVE_PRIORITY_MEDIUM],
      ];
    }
    $header[] = [
      'data' => $this->t('Severity level'),
      'field' => 'level',
      'class' => [RESPONSIVE_PRIORITY_LOW],
    ];
    $header[] = [
      'data' => $this->t('Message'),
      'field' => 'message',
    ];

    $message_table = $migration->getIdMap()->messageTableName();
    $map_table = $migration->getIdMap()->mapTableName();
    $query = $this->database->select($message_table, 'msg')
      ->extend('\Drupal\Core\Database\Query\PagerSelectExtender')
      ->extend('\Drupal\Core\Database\Query\TableSortExtender');
    $query->innerJoin($map_table, 'map', 'msg.source_ids_hash=map.source_ids_hash');
    $query->fields('msg');
    $query->fields('map');
    $result = $query
      ->limit(50)
      ->orderByHeader($header)
      ->execute();

    foreach ($result as $message_row) {
      $column_number = 1;
      foreach ($source_id_field_names as $source_id_field_name) {
        $column_name = 'sourceid' . $column_number++;
        $row[$column_name] = $message_row->$column_name;
      }
      $row['level'] = $message_row->level;
      $row['message'] = $message_row->message;
      $row['class'] = [Html::getClass('migrate-message-' . $message_row->level), $classes[$message_row->level]];
      $rows[] = $row;
    }

    $build['message_table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => ['id' => $message_table, 'class' => [$message_table]],
      '#empty' => $this->t('No messages for this migration.'),
    ];
    $build['message_pager'] = ['#type' => 'pager'];

    return $build;
  }

}
