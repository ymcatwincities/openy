<?php

namespace Drupal\openy_prgf_campaign_menu;

use Drupal\node\NodeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class CampaignMenuService.
 *
 * @package Drupal\openy_prgf_campaign_menu
 */
class CampaignMenuService implements CampaignMenuServiceInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The service container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

  /**
   * Constructs a new CampMenuService.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The current service container.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ContainerInterface $container) {
    $this->entityTypeManager = $entity_type_manager;
    $this->container = $container;
  }

  /**
   * Retrieves referenced Campaign node for the node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   *
   * @return \Drupal\node\NodeInterface|null
   *   Campaign node or NULL.
   */
  public function getNodeCampaignNode(NodeInterface $node) {
    $campaign = NULL;
    if ($campaign_service = $this->container->get('openy_campaign.campaign_service')) {
      $campaign = $campaign_service->getNodeCampNode($node);
    }

    return $campaign;
  }

  /**
   * Retrieves Campaign menu for any node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   *
   * @return array
   *   Array of menu links.
   */
  public function getNodeCampaignMenu(NodeInterface $node) {
    if (!($campaign = $this->getNodeCampaignNode($node))) {
      return [];
    }

    return $this->getCampaignNodeCampaignMenu($campaign);
  }

  /**
   * Retrieves Campaign menu for the Campaign CT node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The Campaign node.
   *
   * @return array
   *   Array of menu links.
   */
  private function getCampaignNodeCampaignMenu(NodeInterface $node) {
    if ($node->bundle() != 'campaign') {
      return [];
    }

    $links = [];
    //@TODO: add campaign-related links according to the campaign status.

    return $links;
  }

}
