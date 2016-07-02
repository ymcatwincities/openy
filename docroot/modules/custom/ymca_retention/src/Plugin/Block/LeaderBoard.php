<?php

namespace Drupal\ymca_retention\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a leader board block.
 *
 * @Block(
 *   id = "retention_leaderBoard_block",
 *   admin_label = @Translation("YMCA retention leader board block"),
 *   category = @Translation("YMCA Blocks")
 * )
 */
class LeaderBoard extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#marckup' => 'listing here',
    ];
  }

}
