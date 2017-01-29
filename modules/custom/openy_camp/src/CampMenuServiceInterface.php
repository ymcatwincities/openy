<?php

namespace Drupal\openy_camp;

use Drupal\node\NodeInterface;

/**
 * Class CampMenuService.
 *
 * @package Drupal\openy_camp
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
