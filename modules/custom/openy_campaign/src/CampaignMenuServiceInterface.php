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
   * Place new landing page Content area paragraphs instead of current ones.
   *
   * @param int $landing_page_id Landing page node ID to get new content for replacement.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function ajaxReplaceLandingPage($landing_page_id);

}
