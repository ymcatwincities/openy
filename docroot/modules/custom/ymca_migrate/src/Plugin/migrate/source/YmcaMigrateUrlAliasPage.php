<?php

/**
 * @file
 * Contains source plugin for url aliases.
 */

namespace Drupal\ymca_migrate\Plugin\migrate\source;

use Drupal\ymca_migrate\Plugin\migrate\YmcaQueryBuilder;
use Drupal\ymca_migrate\Plugin\migrate\YmcaMigrateUrlAliasBase;

/**
 * Source plugin for url aliases.
 *
 * @MigrateSource(
 *   id = "ymca_migrate_url_alias_page"
 * )
 */
class YmcaMigrateUrlAliasPage extends YmcaMigrateUrlAliasBase {

  /**
   * {@inheritdoc}
   */
  protected function getRequirements() {
    return [
      'ymca_migrate_node_page',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query_builder = new YmcaQueryBuilder($this->getDatabase());
    $query_builder->getAllChildren(4693);
    return $query_builder->build();
  }

}
