<?php

namespace Drupal\openy_upgrade_tool;

use Drupal\Core\Config\DatabaseStorage;

/**
 * Defines the Database storage.
 *
 * Note: this storage used only for diff logic.
 */
class OpenyUpgradeToolRevisionStorage extends DatabaseStorage {

  /**
   * {@inheritdoc}
   */
  public function exists($name) {
    $params = $this->decode($name);
    $name = $params['name'] ?? NULL;
    $revision_id = $params['revision_id'] ?? NULL;
    try {
      return (bool) $this->connection->queryRange('SELECT 1 FROM {' . $this->connection->escapeTable($this->table) . '} WHERE name = :name AND vid = :vi', 0, 1, [
        ':name' => $name,
        ':vid' => $revision_id,
      ], $this->options)->fetchField();
    }
    catch (\Exception $e) {
      // If we attempt a read without actually having the database or the table
      // available, just return FALSE so the caller can handle it.
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function read($name) {
    $params = $this->decode($name);
    $name = $params['name'] ?? NULL;
    $revision_id = $params['revision_id'] ?? NULL;
    $data = FALSE;
    try {
      $raw = $this->connection->query('SELECT data FROM {' . $this->connection->escapeTable($this->table) . '} WHERE name = :name AND vid = :vid', [
        ':vid' => $revision_id,
        ':name' => $name,
      ], $this->options)->fetchField();
      if ($raw !== FALSE) {
        $data = $this->decode($raw);
      }
    }
    catch (\Exception $e) {
      // If we attempt a read without actually having the database or the table
      // available, just return FALSE so the caller can handle it.
    }
    return $data;
  }

}
