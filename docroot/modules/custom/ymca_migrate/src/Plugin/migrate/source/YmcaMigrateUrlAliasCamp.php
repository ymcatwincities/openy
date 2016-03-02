<?php

namespace Drupal\ymca_migrate\Plugin\migrate\source;

use Drupal\ymca_migrate\Plugin\migrate\YmcaQueryBuilder;
use Drupal\ymca_migrate\Plugin\migrate\YmcaMigrateUrlAliasBase;

/**
 * Source plugin for url aliases.
 *
 * @MigrateSource(
 *   id = "ymca_migrate_url_alias_camp"
 * )
 */
class YmcaMigrateUrlAliasCamp extends YmcaMigrateUrlAliasBase {

  /**
   * {@inheritdoc}
   */
  protected function getRequirements() {
    return [
      'ymca_migrate_node_camp',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query_builder = new YmcaQueryBuilder($this->getDatabase());
    $query_builder->getByBundle('camp');
    if ($this->isDev()) {
      $query_builder->setRange(0, 5);
    }
    return $query_builder->build();
  }

}
