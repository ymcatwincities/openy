<?php

/**
 * @file
 * Contains \Drupal\ymca_migrate\Plugin\migrate\source\YmcaMigrateNodePage.
 */

namespace Drupal\ymca_migrate\Plugin\migrate\source;

use Drupal\ymca_migrate\Plugin\migrate\YmcaMigrateNodeBase;
use Drupal\ymca_migrate\Plugin\migrate\YmcaQueryBuilder;

/**
 * Source plugin for node:article content.
 *
 * @MigrateSource(
 *   id = "ymca_migrate_node_page"
 * )
 */
class YmcaMigrateNodePage extends YmcaMigrateNodeBase {

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
        4563
      ]);
    }
    else {
      $query_builder->getByBundle('page');
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
      'field_content' => $this->t('Content'),
      'field_lead_description' => $this->t('Content'),
      'field_header_button' => $this->t('Header button'),
      'field_header_variant' => $this->t('Header variant'),
      'field_sidebar' => $this->t('Sidebar'),
      'field_secondary_sidebar' => $this->t('Secondary sidebar'),
    ];
    return $fields;
  }

}
