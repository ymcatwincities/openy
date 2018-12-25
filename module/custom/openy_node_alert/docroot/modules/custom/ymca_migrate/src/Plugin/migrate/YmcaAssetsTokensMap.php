<?php

namespace Drupal\ymca_migrate\Plugin\migrate;

use Drupal\migrate\MigrateException;

/**
 * Class YmcaTokensMap.
 *
 * @package Drupal\ymca_migrate\Plugin\migrate
 */
class YmcaAssetsTokensMap {

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

    // @todo obtain dynamically list of menu migrations.
    $ids = [
      'ymca_migrate_file',
      'ymca_migrate_file_image',
    ];
    $this->migrations = \Drupal::entityManager()
      ->getStorage('migration')
      ->loadMultiple($ids);
  }

  /**
   * Get File ID by Source Asset ID.
   *
   * @param int $source_id
   *   AMM Asset ID.
   *
   * @return bool|int
   *   FALSE if not found, integer of File Item ID otherwise.
   *
   * @throws \Drupal\migrate\MigrateException
   *   Exception throws if something goes wrong.
   */
  public function getAssetId($source_id = NULL) {
    if ($source_id == NULL) {
      throw new MigrateException(sprintf('Can\'t obtain file for zero iD'));
    }
    /* @var \Drupal\migrate\Entity\Migration $migration */
    foreach ($this->migrations as $id => $migration) {
      $map = $migration->getIdMap();
      $dest = $map->getRowBySource(array('asset_id' => $source_id, 'id' => $source_id));
      if (!empty($dest)) {
        return $dest['destid1'];
      }

    }
    return FALSE;
  }

}
