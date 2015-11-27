<?php

/**
 * @file
 * Contains source plugin for migration menu links.
 */

namespace Drupal\ymca_migrate\Plugin\migrate\source;
use Drupal\ymca_migrate\Plugin\migrate\YmcaMigrateMenuLinkContentBase;

/**
 * Source plugin for menu_link_content items.
 *
 * @MigrateSource(
 *   id = "ymca_migrate_menu_link_content_swimming"
 * )
 */
class YmcaMigrateMenuLinkContentSwimming extends YmcaMigrateMenuLinkContentBase {

  /**
   * {@inheritdoc}
   */
  public function getMenu() {
    return 'swimming';
  }

  /**
   * {@inheritdoc}
   */
  public function getParentId() {
    return 4693;
  }

}
