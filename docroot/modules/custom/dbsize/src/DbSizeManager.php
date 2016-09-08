<?php

namespace Drupal\dbsize;

use Drupal\Core\Database\Connection;

/**
 * Class DbSizeManager.
 */
class DbSizeManager {

  /**
   * Connection.
   *
   * @var Connection
   */
  protected $connection;

  /**
   * DbSizeTable constructor.
   *
   * @param Connection $connection
   *   The DB connection.
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
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

}
