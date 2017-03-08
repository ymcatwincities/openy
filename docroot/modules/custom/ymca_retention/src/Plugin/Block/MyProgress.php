<?php

namespace Drupal\ymca_retention\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a block with personal progress.
 *
 * @Block(
 *   id = "retention_my_progress_block",
 *   admin_label = @Translation("[YMCA Retention] My Progress"),
 *   category = @Translation("YMCA Blocks")
 * )
 */
class MyProgress extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#theme' => 'ymca_retention_my_progress',
      '#attached' => [
        'library' => [
          'ymca_retention/my-progress',
        ],
      ],
    ];
  }

}
