<?php

namespace Drupal\openy_campaign;

use Drupal\node\NodeInterface;

/**
 * Class CampaignMenuService.
 *
 * @package Drupal\openy_campaign
 */
interface CampaignMenuServiceInterface {

  /**
   * Retrieves referenced Campaign node for the node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   *
   * @return \Drupal\node\NodeInterface|null
   *   Campaign node or NULL.
   */
  public function getNodeCampaignNode(NodeInterface $node);

  /**
   * Retrieves Campaign menu for any node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   *
   * @return array
   *   Array of menu links.
   */
  public function getNodeCampaignMenu(NodeInterface $node);

}
