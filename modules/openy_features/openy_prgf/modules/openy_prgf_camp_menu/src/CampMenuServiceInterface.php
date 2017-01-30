<?php

namespace Drupal\openy_prgf_camp_menu;

use Drupal\node\NodeInterface;

/**
 * Class CampMenuService.
 *
 * @package Drupal\openy_prgf_camp_menu
 */
interface CampMenuServiceInterface {

  /**
   * Retrieves referenced Camp node for the node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   *
   * @return \Drupal\node\NodeInterface|null
   *   Camp node or NULL.
   */
  public function getNodeCampNode(NodeInterface $node);

  /**
   * Retrieves Camp menu for any node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   *
   * @return array
   *   Array of menu links.
   */
  public function getNodeCampMenu(NodeInterface $node);

}
