<?php

/**
 * @file
 * Contains migration for locations.
 */

namespace Drupal\ymca_migrate\Plugin\migrate\source;

use Drupal\ymca_migrate\Plugin\migrate\YmcaMigrateNodeBase;
use Drupal\ymca_migrate\Plugin\migrate\YmcaQueryBuilder;

/**
 * Source plugin for node:location content.
 *
 * @MigrateSource(
 *   id = "ymca_migrate_node_location"
 * )
 */
class YmcaMigrateNodeLocation extends YmcaMigrateNodeBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query_builder = new YmcaQueryBuilder($this->getDatabase());
    $query_builder->getByBundle('location');
    if ($this->isDev()) {
      $query_builder->setRange(0, 5);
    }
    return $query_builder->build();
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'site_page_id' => $this->t('Page ID'),
      'page_title' => $this->t('Page title'),
    ];
    return $fields;
  }

}
