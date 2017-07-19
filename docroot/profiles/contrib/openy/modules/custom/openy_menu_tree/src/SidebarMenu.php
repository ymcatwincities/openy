<?php

namespace Drupal\openy_menu_tree;

use Drupal\node\NodeInterface;

/**
 * Class SidebarMenu.
 *
 * Helps to find appropriate side bar menu for the page.
 *
 * @package Drupal\openy_menu_tree
 */
class SidebarMenu implements SidebarMenuInterface {

  /**
   * {@inheritdoc}
   */
  public function findMenu(NodeInterface $node) {
    $item = menu_ui_get_menu_link_defaults($node);
    if (isset($item['menu_name']) && isset($item['id']) && !empty($item['id'])) {
      return $item['menu_name'];
    }
    return FALSE;
  }

}
