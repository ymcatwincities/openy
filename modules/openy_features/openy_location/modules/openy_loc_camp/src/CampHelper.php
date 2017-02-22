<?php

namespace Drupal\openy_loc_camp;

use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;

/**
 * Class Camp Helper functions.
 */
class CampHelper {

  /**
   * Helper that returns the Camp CT associated to the landing page provided.
   *
   * @param \Drupal\openy_prgf_camp_menu\NodeInterface $node
   *   The node.
   */
  public static function getLandingPageCampNode(NodeInterface $node) {
    $camp = NULL;
    if ($node->bundle() != 'landing_page') {
      return $camp;
    }

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

    return $camp;
  }

}
