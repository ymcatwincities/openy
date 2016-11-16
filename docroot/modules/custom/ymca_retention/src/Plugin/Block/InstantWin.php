<?php

namespace Drupal\ymca_retention\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides an instant win block.
 *
 * @Block(
 *   id = "retention_instant_win_block",
 *   admin_label = @Translation("[YMCA Retention] Instant win"),
 *   category = @Translation("YMCA Blocks")
 * )
 */
class InstantWin extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#theme' => 'ymca_retention_instant_win',
      '#content' => [],
      '#attached' => [
        'library' => [
          'ymca_retention/instant-win',
        ],
      ],
    ];
  }

}
