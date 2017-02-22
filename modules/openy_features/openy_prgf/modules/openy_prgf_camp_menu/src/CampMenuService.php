<?php

namespace Drupal\openy_prgf_camp_menu;

use Drupal\node\NodeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\Entity\Node;
use Drupal\openy_loc_camp\CampHelper;

/**
 * Class CampMenuService.
 *
 * @package Drupal\openy_prgf_camp_menu
 */
class CampMenuService implements CampMenuServiceInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new CampMenuService.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Retrieves referenced Camp node for the node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   *
   * @return \Drupal\node\NodeInterface|null
   *   Camp node or NULL.
   */
  public function getNodeCampNode(NodeInterface $node) {
    $camp = NULL;
    switch ($node->bundle()) {
      case 'camp':
        $camp = $node;
        break;

      case 'landing_page':
        $camp = CampHelper::getLandingPageCampNode($node);
        break;
    }

    return $camp;
  }

  /**
   * Retrieves Camp menu for any node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   *
   * @return array
   *   Array of menu links.
   */
  public function getNodeCampMenu(NodeInterface $node) {
    if (!$camp = $this->getNodeCampNode($node)) {
      return [];
    }

    return $this->getCampNodeCampMenu($camp);
  }

  /**
   * Retrieves Camp menu for the Camp CT node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The Camp node.
   *
   * @return array
   *   Array of menu links.
   */
  private function getCampNodeCampMenu(NodeInterface $node) {
    if ($node->bundle() != 'camp') {
      return [];
    }

    $links = [];
    foreach ($node->field_camp_menu_links->getValue() as $link) {
      $links[] = $link;
    }

    return $links;
  }

}
