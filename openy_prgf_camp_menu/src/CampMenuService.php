<?php

namespace Drupal\openy_prgf_camp_menu;

use Drupal\node\NodeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use \Drupal\node\Entity\Node;

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
        if ($node->hasField('field_location')) {
          if ($value = $node->field_location->referencedEntities()) {
            $camp = reset($value);
          }
        }
        // Else if a camp links to this landing page use the linking camp.
        else {
          // Query 1 Camp nodes that link to this landing page.
          $query = \Drupal::entityQuery('node')
            ->condition('status', 1)
            ->condition('type', 'camp')
            ->condition('field_camp_menu_links', 'entity:node/' . $node->id())
            ->range(0, 1);
          $entity_ids = $query->execute();
          // If results returned.
          if (!empty($entity_ids)) {
            $camp = Node::load(reset($entity_ids));
          }
        }
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
