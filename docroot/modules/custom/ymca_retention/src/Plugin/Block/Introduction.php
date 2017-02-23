<?php

namespace Drupal\ymca_retention\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a block with campaign introduction info.
 *
 * @Block(
 *   id = "retention_introduction_block",
 *   admin_label = @Translation("[YMCA Retention] Introduction"),
 *   category = @Translation("YMCA Blocks")
 * )
 */
class Introduction extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#theme' => 'ymca_retention_introduction',
      '#attached' => [
        'library' => [
          'ymca_retention/introduction',
        ],
      ],
    ];
  }

}
