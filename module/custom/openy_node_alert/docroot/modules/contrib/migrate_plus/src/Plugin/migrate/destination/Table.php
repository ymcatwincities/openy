<?php

namespace Drupal\migrate_plus\Plugin\migrate\destination;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateSkipProcessException;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\migrate\destination\DestinationBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides table destination plugin.
 *
 * Use this plugin for a table not registered with Drupal Schema API.
 *
 * @MigrateDestination(
 *   id = "table"
 * )
 */
class Table extends DestinationBase implements ContainerFactoryPluginInterface {

  /**
   * The name of the destination table.
   *
   * @var string
   */
  protected $tableName;

  /**
   * IDMap compatible array of id fields.
   *
   * @var array
   */
  protected $idFields;

  /**
   * Array of fields present on the destination table.
   *
   * @var array
   */
  protected $fields;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $dbConnection;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, Connection $connection) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
    $this->dbConnection = $connection;
    $this->tableName = $configuration['table_name'];
    $this->idFields = $configuration['id_fields'];
    $this->fields = isset($configuration['fields']) ? $configuration['fields'] : [];
    $this->supportsRollback = TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    $db_key = !empty($configuration['database_key']) ? $configuration['database_key'] : NULL;

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      Database::getConnection('default', $db_key)
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    if (empty($this->idFields)) {
      throw new MigrateException('Id fields are required for a table destination');
    }
    return $this->idFields;
  }

  /**
   * {@inheritdoc}
   */
  public function fields(MigrationInterface $migration = NULL) {
    return $this->fields;
  }

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = []) {
    $id = $row->getSourceIdValues();
    if (count($id) != count($this->idFields)) {
      throw new MigrateSkipProcessException('All the id fields are required for a table migration.');
    }

    $values = $row->getDestination();

    if ($this->fields) {
      $values = array_intersect_key($values, $this->fields);
    }

    $status = $this->dbConnection->merge($this->tableName)
      ->key($id)
      ->fields($values)
      ->execute();

    return $status ? $id : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function rollback(array $destination_identifier) {
    $delete = $this->dbConnection->delete($this->tableName);
    foreach ($destination_identifier as $field => $value) {
      $delete->condition($field, $value);
    }
    $delete->execute();
  }
}
