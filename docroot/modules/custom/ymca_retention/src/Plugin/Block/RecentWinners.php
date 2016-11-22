<?php

namespace Drupal\ymca_retention\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a block with recent winners.
 *
 * @Block(
 *   id = "retention_recent_winners_block",
 *   admin_label = @Translation("[YMCA Retention] Recent winners"),
 *   category = @Translation("YMCA Blocks")
 * )
 */
class RecentWinners extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#theme' => 'ymca_retention_recent_winners',
      '#attached' => [
        'library' => [
          'ymca_retention/recent-winners',
        ],
      ],
    ];
  }

}
