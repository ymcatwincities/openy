<?php

namespace Drupal\dbsize;

/**
 * Interface for DbSizeManager.
 */
interface DbSizeManagerInterface {

  /**
   * Get size of the table or tables.
   *
   * @param array $tables
   *   Table name
   *
   * @return mixed
   *   Table size in bytes.
   */
  public function getTablesSize(array $tables);

}
