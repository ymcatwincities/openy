<?php

namespace Drupal\ymca_retention\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a leader board block.
 *
 * @Block(
 *   id = "retention_leader_board_block",
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
      '#theme' => 'ymca_retention_leader_board',
    ];
  }

}
