<?php

namespace Drupal\ymca_retention\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a block with personal progress.
 *
 * @Block(
 *   id = "retention_progress_block",
 *   admin_label = @Translation("[YMCA Retention] Progress"),
 *   category = @Translation("YMCA Blocks")
 * )
 */
class Progress extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#theme' => 'ymca_retention_progress',
      '#attached' => [
        'library' => [
          'ymca_retention/progress',
        ],
      ],
    ];
  }

}
