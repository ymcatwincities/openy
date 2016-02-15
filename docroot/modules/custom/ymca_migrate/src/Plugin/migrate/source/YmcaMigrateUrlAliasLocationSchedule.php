<?php

namespace Drupal\ymca_migrate\Plugin\migrate\source;

use Drupal\migrate\Row;

/**
 * Source plugin for url aliases.
 *
 * @MigrateSource(
 *   id = "ymca_migrate_url_alias_location_schedule"
 * )
 */
class YmcaMigrateUrlAliasLocationSchedule extends YmcaMigrateUrlAliasLocation {

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $row->setSourceProperty(
      'source',
      $this->getSourcePath(
        ['site_page_id' => $row->getSourceProperty('site_page_id')]
      ) . '/schedules'
    );

    $row->setSourceProperty(
      'alias',
      rtrim($row->getSourceProperty('page_subdirectory'), '/') . '/schedules'
    );
  }

}
