<?php

namespace Drupal\ymca_migrate\Plugin\migrate;

use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * Base source plugin for url aliases.
 */
abstract class YmcaMigrateUrlAliasBase extends SqlBase {

  use YmcaMigrateTrait;

  /**
   * Required migrations.
   *
   * @var array
   */
  protected $migrations = [];

  /**
   * Return list of required migrations.
   *
   * @return array
   *   Array of migrations.
   */
  abstract protected function getRequirements();

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, $migration, $state) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $state);
    $this->prepopulateMigrations();
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'source' => $this->t('Source path'),
      'alias' => $this->t('Path alias'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $row->setSourceProperty('source', $this->getSourcePath(['site_page_id' => $row->getSourceProperty('site_page_id')]));
    $row->setSourceProperty('alias', rtrim($row->getSourceProperty('page_subdirectory'), '/'));
    return parent::prepareRow($row);
  }

  /**
   * Prepopulate required migrations.
   */
  protected function prepopulateMigrations() {
    $requirements = $this->getRequirements();
    $this->migrations = \Drupal::getContainer()
      ->get('entity.manager')
      ->getStorage('migration')
      ->loadMultiple($requirements);
  }

  /**
   * Get source path.
   *
   * @param array $source
   *   Example: ['site_page_id' => 10].
   *
   * @return bool|string
   *   Source path.
   */
  protected function getSourcePath(array $source) {
    foreach ($this->migrations as $id => $migration) {
      $map = $migration->getIdMap();
      $dest = $map->getRowBySource($source);
      if (!empty($dest)) {
        return sprintf('/node/%d', $dest['destid1']);
      }
    }
    return FALSE;
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

}
