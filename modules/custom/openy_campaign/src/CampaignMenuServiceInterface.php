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
   * Get campaign node from current page URL.
   *
   * @return bool|\Drupal\Node\Entity\Node
   */
  public function getCampaignNodeFromRoute();

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

  /**
   * Get all active Campaign nodes.
   *
   * @return array|null
   *   Array Campaign nodes or null
   */
  public function getActiveCampaigns();

}
