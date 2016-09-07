<?php

namespace Drupal\dbsize;

/**
 * Interface for DbSizeManager.
 */
interface DbSizeManagerInterface {

  /**
   * Get table size.
   *
   * @param string $table
   *   Table name
   *
   * @return mixed
   *   Table size.
   */
  public function getTableSize($table);

}
