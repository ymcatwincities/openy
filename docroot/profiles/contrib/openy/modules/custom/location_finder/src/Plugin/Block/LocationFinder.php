<?php

namespace Drupal\location_finder\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a block with location finder.
 *
 * @Block(
 *   id = "location_finder",
 *   admin_label = @Translation("Location finder"),
 *   category = @Translation("Paragraph Blocks")
 * )
 */
class LocationFinder extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      [
        '#type' => 'view',
        '#name' => 'locations',
        '#display_id' => 'locations_branches_block',
      ],
      [
        '#type' => 'view',
        '#name' => 'locations',
        '#display_id' => 'locations_camps_block',
      ],
      [
        '#type' => 'view',
        '#name' => 'locations',
        '#display_id' => 'locations_facilities_block',
      ],
    ];
  }

}
