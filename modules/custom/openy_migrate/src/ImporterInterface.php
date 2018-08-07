<?php

namespace Drupal\openy_migrate;

/**
 * Interface ImporterInterface.
 *
 * @package Drupal\openy_migrate
 */
interface ImporterInterface {

  /**
   * Import migration by id.
   *
   * @param string $migration_id
   *   Migration ID.
   */
  public function import($migration_id);

  /**
   * Import migrations by tag.
   *
   * @param string $migration_tag
   *   Migration tag.
   */
  public function importByTag($migration_tag);

}
