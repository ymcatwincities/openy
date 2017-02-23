<?php

namespace Drupal\openy_loc_camp;

use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Class CampService contains methods for camp related processes.
 *
 * @package Drupal\openy_loc_camp\CampService
 */
class CampService {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a EntityCreateAccessCheck object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Returns boolean if provided node is a camp or is related to one.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   *
   * @return bool
   *  True when a camp or related to a camp, else False.
   */
  public function nodeHasOrIsCamp(NodeInterface $node) {
    if ($this->getNodeCampNode($node) == NULL) {
      return FALSE;
    }
    return TRUE;
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
        $camp = $this->getLandingPageCampNode($node);
        break;
    }

    return $camp;
  }

  /**
   * Retrieves the Camp CT associated to the landing page provided.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   *
   * @return \Drupal\node\Entity\Node|mixed|null
   *   The camp node or Null.
   */
  public function getLandingPageCampNode(NodeInterface $node) {
    $camp = NULL;
    // Exit if the node is not a landing page.
    if ($node->bundle() != 'landing_page') {
      return $camp;
    }

    // If the node has the Location field.
    if ($node->hasField('field_location')) {
      if ($value = $node->field_location->referencedEntities()) {
        // referencedEntities() returns an array of entities, since the location
        // field can only have a single value we reset to get that node object.
        $camp = reset($value);
      }
    }
    // Else if a camp links to this landing page use the linking camp.
    else {
      // Query 1 Camp nodes that link to this landing page.
      $query = \Drupal::entityQuery('node')
        ->condition('status', 1)
        ->condition('field_camp_menu_links', 'entity:node/' . $node->id())
        ->range(0, 1);
      $entity_ids = $query->execute();
      // If results returned.
      if (!empty($entity_ids)) {
        $node_storage = $this->entityTypeManager->getStorage('node');
        $camp = $node_storage->load(reset($entity_ids));
//        $camp = Node::load();
      }
    }

    return $camp;
  }

}
