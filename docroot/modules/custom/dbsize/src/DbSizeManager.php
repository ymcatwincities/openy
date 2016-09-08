<?php

namespace Drupal\dbsize;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\ContentEntityType;
use Drupal\Core\Entity\EntityTypeManagerInterface;

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
   * DbSizeTable constructor.
   *
   * @param Connection $connection
   *   The DB connection.
   * @param EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   */
  public function __construct(Connection $connection, EntityTypeManagerInterface $entity_type_manager) {
    $this->connection = $connection;
    $this->entityTypeManager = $entity_type_manager;
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
  public function getEntitySize($entityType) {
    /** @var ContentEntityType $type */
    $type = $this->entityTypeManager->getDefinition('groupex_form_cache');
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

    // @todo Add tables for fields.

    return $this->getTablesSize($tables);
  }

}
