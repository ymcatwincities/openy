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
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Page title could be empty, use page_name instead.
    if ($title = $row->getSourceProperty('page_title')) {
      $row->setSourceProperty('title', $title);
    }
    else {
      $row->setSourceProperty('title', $row->getSourceProperty('page_name'));
    }

    $row->setSourceProperty('menu_name', $this->getMenu());
    $row->setSourceProperty('link', ['uri' => 'internal:/']);

    $row->setSourceProperty('enabled', TRUE);
    if ($row->getSourceProperty('exclude_from_nav')) {
      $row->setSourceProperty('enabled', FALSE);
    }

    if (in_array($row->getSourceProperty('site_page_id'), $this->getParentIds())) {
      $row->setSourceProperty('parent_id', 0);
    }
  }

}
