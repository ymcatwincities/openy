<?php

/**
 * @file
 * Contains source plugin for migration menu links.
 */

namespace Drupal\ymca_migrate\Plugin\migrate\source;

use Drupal\ymca_migrate\Plugin\migrate\YmcaQueryBuilder;
use Drupal\migrate\Row;

/**
 * Source plugin for menu_link_content items.
 *
 * @MigrateSource(
 *   id = "ymca_migrate_menu_link_content_jobs_suppliers_news"
 * )
 */
class YmcaMigrateMenuLinkContentJobsSuppliersNews extends YmcaMigrateMenuLinkContent {

  /**
   * {@inheritdoc}
   */
  public function query() {
    /* @var YmcaQueryBuilder $query_builder */
    $query_builder = new YmcaQueryBuilder($this->getDatabase());
    foreach ($this->getParentIds() as $item) {
      $query_builder->getAllChildren($item);
    }
    $query = $query_builder->build();
    return $query;
  }

  /**
   * Get parent IDs item for the menu items.
   *
   * @return array
   *   Parent IDs.
   */
  public function getParentIds() {
    $constants = $this->migration->get('source')['constants'];
    if (isset($constants['menu_parent_ids'])) {
      return $constants['menu_parent_ids'];
    }
    return NULL;
  }

}
