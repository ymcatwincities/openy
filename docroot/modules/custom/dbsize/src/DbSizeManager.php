<?php

namespace Drupal\dbsize;

use Drupal\Core\Database\Connection;

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

  public function getTableSize($table) {
    // @todo Narrow results by selecting certain table.
    $options = $this->connection->getConnectionOptions();
    $result = $this->connection->query('SELECT * FROM information_schema.TABLES;');
    foreach ($result as $item) {
      if ($item->TABLE_SCHEMA == $options['database'] && $item->TABLE_NAME == $table) {
        // @todo Return results in bytes + use some formatting.
        return round((($item->DATA_LENGTH + $item->INDEX_LENGTH) / 1024 / 1024), 2) . 'M';
      }
    }

    return FALSE;
  }

}
