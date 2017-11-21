<?php

namespace Drupal\openy_menu_tree;

use Drupal\node\NodeInterface;

interface SidebarMenuInterface {

  /**
   * Find appropriate SideBar menu for the page.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Node.
   *
   * @return string
   *   Menu ID.
   */
  public function findMenu(NodeInterface $node);

}
