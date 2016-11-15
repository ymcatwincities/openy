<?php

namespace Drupal\ymca_retention\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;

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
        'drupalSettings' => [
          'ymca_retention' => [
            'checkins' => [
              'checkins_history_url' => Url::fromRoute('ymca_retention.member_checkins_history_json')->toString(),
            ],
          ],
        ],
      ],
    ];
  }

}
