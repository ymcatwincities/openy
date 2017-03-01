<?php

namespace Drupal\ymca_retention\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides an instant win block.
 *
 * @Block(
 *   id = "retention_todays_insight_block",
 *   admin_label = @Translation("[YMCA Retention] Today's insight"),
 *   category = @Translation("YMCA Blocks")
 * )
 */
class TodaysInsight extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#theme' => 'ymca_retention_todays_insight',
      '#content' => [],
      '#attached' => [
        'library' => [
          'ymca_retention/todays-insight',
        ],
      ],
    ];
  }

}
