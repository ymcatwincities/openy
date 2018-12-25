<?php

namespace Drupal\ymca_migrate\Plugin\migrate;

use Drupal\migrate\MigrateException;

/**
 * Class YmcaTokensMap.
 *
 * @package Drupal\ymca_migrate\Plugin\migrate
 */
class YmcaTokensMap {

  /**
   * Array of migrations to search.
   *
   * @var array
   */
  private $migrations;

  /**
   * YmcaTokensMap constructor.
   */
  public function __construct() {
    $this->prepopulateMigrations();
  }

  /**
   * Internal migrations getter.
   */
  private function prepopulateMigrations() {
    // Get a list of menu link content migrations.
    $dir = drupal_get_path('module', 'ymca_migrate') . '/config/install';
    $list = file_scan_directory($dir, '/migrate.migration.ymca_migrate_menu_link_content_*/');
    $ids = [];
    foreach ($list as $item) {
      $ids[] = str_replace('migrate.migration.', '', $item->name);
    }
    $this->migrations = \Drupal::entityManager()
      ->getStorage('migration')
      ->loadMultiple($ids);
  }

  /**
   * Get Menu ID by Source Page ID.
   *
   * @param array $source_id
   *   AMM Page ID array ['source_page_id' => (int)ID].
   *
   * @return bool|int
   *   FALSE if not found, integer of Menu Item ID otherwise.
   *
   * @throws \Drupal\migrate\MigrateException
   *   Exception throws if something goes wrong.
   */
  public function getMenuId($source_id = NULL) {
    if ($source_id == NULL) {
      throw new MigrateException(sprintf('Can\'t obtain menu for zero iD'));
    }
    /* @var \Drupal\migrate\Entity\Migration $migration */
    foreach ($this->migrations as $id => $migration) {
      $map = $migration->getIdMap();
      $dest = $map->getRowBySource(array('site_page_id' => $source_id));
      if (!empty($dest)) {
        return $dest['destid1'];
      }

    }
    return FALSE;
  }

}
