<?php

namespace Drupal\dbsize;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\field\FieldStorageConfigInterface;

/**
 * Class DbSizeManager.
 */
class DbSizeManager implements DbSizeManagerInterface {

  /**
   * Connection.
   *
   * @var Connection
   */
  protected $connection;

  /**
   * Entity type manager.
   *
   * @var EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Entity field manager.
   *
   * @var EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * DbSizeTable constructor.
   *
   * @param Connection $connection
   *   The DB connection.
   * @param EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @param EntityFieldManagerInterface $entity_field_manager
   *   Entity field manager.
   */
  public function __construct(Connection $connection, EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager) {
    $this->connection = $connection;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getTablesSize(array $tables) {
    if (empty($tables)) {
      return FALSE;
    }

    $options = $this->connection->getConnectionOptions();

    $q = 'SELECT * FROM information_schema.TABLES t WHERE t.TABLE_SCHEMA = :db';
    $result = $this->connection->query($q, [':db' => $options['database']]);

    $length = 0;
    foreach ($result as $table) {
      if (in_array($table->TABLE_NAME, $tables)) {
        $length += $table->DATA_LENGTH + $table->INDEX_LENGTH;
      }
    }

    return empty($length) ? FALSE : $length;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntitySize($entity_type_id) {
    $type = $this->entityTypeManager->getDefinition($entity_type_id);

    // Currently we support only entities without bundles.
    if ($type->getBundleOf()) {
      // @todo Add support for entities with bundles.
      return FALSE;
    }

    $tables = [];

    // Add base table.
    $tables[] = $type->getBaseTable();

    // Add data table.
    if ($type->getDataTable()) {
      $tables[] = $type->getDataTable();
    }

    // Add revision tables.
    if ($type->isRevisionable()) {
      if ($type->getRevisionTable()) {
        $tables[] = $type->getRevisionTable();
      }

      if ($type->getRevisionDataTable()) {
        $tables[] = $type->getRevisionDataTable();
      }
    }

    // Get all fields.
    $fields = $this->entityFieldManager->getFieldStorageDefinitions($entity_type_id);

    foreach ($fields as $field) {
      if (!$field->isBaseField()) {
        // @todo Find proper way to get table names.
        // @todo Find proper way to get tables with revisions.
        $tables[] = $field->getTargetEntityTypeId() . '__' . $field->getName();
      }
    }

    return $this->getTablesSize($tables);
  }

}
