<?php

namespace Drupal\openy_block_branch_amenities\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\node\NodeInterface;

/**
 * Provides a 'Node Amenities' block.
 *
 * @Block(
 *   id = "branch_amenities_icons",
 *   admin_label = @Translation("Branch Amenities with icons Block"),
 *   category = @Translation("Paragraph Blocks")
 * )
 */
class AmenitiesWithIcons extends BlockBase {

  /**
   * @todo add cache
   * {@inheritdoc}
   */
  public function build() {
    $render_array = [];

    $node = \Drupal::routeMatch()->getParameter('node');
    if ($node instanceof NodeInterface && $node->hasField('field_location_amenities')) {
      return $node->get('field_location_amenities')->view([
        'type' => 'entity_reference_entity_view',
        'settings' => [
          'view_mode' => 'icon'
        ]
      ]);
    }

    return $render_array;
  }
}

