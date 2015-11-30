<?php
/**
 * @file
 * Implements service for Token Map getter.
 */

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
   * @var array.
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

    // @todo obtain dynamically list of menu migrations.
    $ids = [
      'ymca_migrate_menu_link_content_kid_teen_activities',
      'ymca_migrate_menu_link_content_camps',
      'ymca_migrate_menu_link_content_child_care_preschool',
      'ymca_migrate_menu_link_content_community_programs',
      'ymca_migrate_menu_link_content_health_fitness',
      'ymca_migrate_menu_link_content_locations',
      'ymca_migrate_menu_link_content_main',
      'ymca_migrate_menu_link_content_swimming'
    ];
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
