<?php

namespace Drupal\ymca_migrate\Plugin\migrate\source;

use Drupal\ymca_migrate\Plugin\migrate\YmcaQueryBuilder;

/**
 * Source plugin for menu_link_content items.
 *
 * @MigrateSource(
 *   id = "ymca_migrate_menu_link_content_main"
 * )
 */
class YmcaMigrateMenuLinkContentMain extends YmcaMigrateMenuLinkContent {

  /**
   * {@inheritdoc}
   */
  public function query() {
    /* @var YmcaQueryBuilder $query_builder */
    $query_builder = new YmcaQueryBuilder($this->getDatabase());
    $query_builder->getList([
      4552,
      16403,
      6708,
      6710,
      6718,
      6722,
    ]);
    return $query_builder->build();
  }

}
