<?php

namespace Drupal\openy_migrate;

/**
 * Interface ImporterInterface.
 *
 * @package Drupal\openy_migrate
 */
interface ImporterInterface {

  /**
   * Import migration.
   *
   * @param string $migration_id
   *   Migration ID.
   */
  public function import($migration_id);

}
