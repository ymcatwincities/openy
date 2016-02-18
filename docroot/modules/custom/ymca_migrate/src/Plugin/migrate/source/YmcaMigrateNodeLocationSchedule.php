<?php

namespace Drupal\ymca_migrate\Plugin\migrate\source;

use Drupal\Core\State\StateInterface;
use Drupal\migrate\Entity\MigrationInterface;
use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;
use Drupal\ymca_migrate\Plugin\migrate\YmcaMigrateTrait;

/**
 * Source plugin definition.
 *
 * Schedules are separate pages in legacy database. The new architecture
 * implies that schedule content is part of Location entity. In this migration
 * we just import data from the legacy DB and update existing Location nodes.
 *
 * @MigrateSource(
 *   id = "ymca_migrate_node_location_schedule"
 * )
 */
class YmcaMigrateNodeLocationSchedule extends SqlBase {

  use YmcaMigrateTrait;

  /**
   * Theme ID for schedule pages.
   *
   * @var int
   */
  static private $theme = 31;

  /**
   * Area ID for schedule components.
   *
   * @var int
   */
  static private $area = 4;

  /**
   * List of required migrations.
   *
   * @var array
   */
  private $requirements = [];

  /**
   * List of required migrations entities.
   *
   * @var array
   */
  private $migrations = [];

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, StateInterface $state) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $state);
    $this->prepopulateMigrations();
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    if ($this->isDev()) {
      return $this->select('amm_site_page', 'p')
        ->fields('p')
        ->condition('site_page_id', [7836, 7885, 7926, 7952, 7973, 8015], 'IN');
    }
    else {
      return $this->select('amm_site_page', 'p')
        ->fields('p')
        ->condition('theme_id', self::$theme);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $page_id = $row->getSourceProperty('site_page_id');

    // Get location.
    $parent_id = $row->getSourceProperty('parent_id');
    if (!$lid = $this->getDestination('location', ['site_page_id' => $parent_id])) {
      $this->idMap->saveMessage(
        $this->getCurrentIds(),
        $this->t(
          "[LEAD] It seems the parent location is still not migrated [@page]",
          array('@page' => $row->getSourceProperty('parent_id'))
        ),
        MigrationInterface::MESSAGE_ERROR
      );
      return FALSE;
    }

    // Set location as destination.
    $row->setSourceProperty('nid', $lid);

    // Prepare schedule content.
    $rich = $this->select('amm_site_page_component', 'c')
      ->fields('c', ['body'])
      ->condition('site_page_id', $page_id)
      ->condition('content_area_index', self::$area)
      ->condition('component_type', 'rich_text')
      ->execute()
      ->fetchField();
    $row->setSourceProperty('schedule_content', $rich);

    // Prepare documents.
    // Get parent component ID with asset list.
    $pid = $this->select('amm_site_page_component', 'c')
      ->fields('c', ['extra_data_1'])
      ->condition('site_page_id', $page_id)
      ->condition('content_area_index', self::$area)
      ->condition('component_type', 'content_block_join')
      ->execute()
      ->fetchField();
    $asset_text = $this->select('amm_site_page_component', 'c')
      ->fields('c', ['body'])
      ->condition('parent_component_id', $pid)
      ->execute()
      ->fetchField();
    $source_assets_ids = $this->getAssets($asset_text);

    // Provide hardcoded assets for dev environment.
    if ($this->isDev()) {
      $source_assets_ids = [
        8375 => t('Test label 1'),
        8376 => t('Test label 2')
      ];
    }
    // No assets? Returning.
    if (!is_array($source_assets_ids)) {
      return parent::prepareRow($row);
    }
    $assets = [];
    foreach ($source_assets_ids as $source_id => $title) {
      if ($dest_asset_id = $this->getDestination('asset', ['asset_id' => $source_id])) {
        $assets[] = [
          'target_id' => $dest_asset_id,
          'description' => $title,
          'display' => TRUE,
        ];
      }
      else {
        $this->idMap->saveMessage(
          $this->getCurrentIds(),
          $this->t(
            "[LEAD] It seems the asset is still not migrated [@asset]",
            array('@asset' => $source_id)
          ),
          MigrationInterface::MESSAGE_WARNING
        );
      }
    }
    $row->setSourceProperty('schedule_documents', $assets);

    return parent::prepareRow($row);
  }

  /**
   * Extract assets from text.
   *
   * @param string $string
   *   A text to parse.
   *
   * @return array
   *   List of source asset IDs and titles.
   */
  private function getAssets($string) {
    $ids = [];

    $regex = "/.*<li><a[^{}]*{{internal_asset_link_(\d+)}}.*>(.*)<\/a><\/li>/";
    preg_match_all($regex, $string, $test);

    if (empty($test) || empty($test[0])) {
      return FALSE;
    }
    foreach ($test[0] as $key => $item) {
      $ids[$test[1][$key]] = $test[2][$key];
    }

    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'site_page_id' => $this->t('Page ID'),
    ];
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'site_page_id' => [
        'type' => 'integer',
        'alias' => 'p',
      ],
    ];
  }

  /**
   * Get location by source ID.
   *
   * @param string $scope
   *   A scope to search in.
   * @param array $source
   *   Example: ['site_page_id' => 10].
   *
   * @return mixed
   *   Destination ID or FALSE.
   */
  private function getDestination($scope, array $source) {
    foreach ($this->migrations[$scope] as $id => $migration) {
      $map = $migration->getIdMap();
      $dest = $map->getRowBySource($source);
      if (!empty($dest)) {
        return $dest['destid1'];
      }
    }

    return FALSE;
  }

  /**
   * Prepopulate required migrations.
   */
  private function prepopulateMigrations() {
    $this->requirements['location'] = [
      'ymca_migrate_node_location',
    ];

    $this->requirements['asset'] = [
      'ymca_migrate_file_image',
    ];

    foreach ($this->requirements as $scope => $list) {
      $this->migrations[$scope] = \Drupal::getContainer()
        ->get('entity.manager')
        ->getStorage('migration')
        ->loadMultiple($this->requirements[$scope]);
    }
  }

}
