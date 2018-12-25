<?php

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
    if ($this->isDev()) {
      $query_builder->getList([
        4693,
        8595,
        4765,
        4747,
        8659,
        4793,
        14641,
        22528,
        4695,
        4792,
        4794,
        8652,
        20235,
        8601,
        4795,
        4711,
        4796,
        8642,
        20256,
        4744,
        4797,
        4717,
        4798,
        4738,
        4720,
        20247,
        20249,
        4721,
        4745,
        4748,
        4754,
        4757,
        4799,
        4729,
        4746,
        4749,
        4755,
        4758,
        20243,
        4750,
        4756,
        15824,
        4751,
        4759,
        4763,
        4752,
        4760,
        4753,
        5100,
      ]);
    }
    else {
      $query_builder->getByBundle('page');
    }
    return $query_builder->build();
  }

}
