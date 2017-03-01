<?php

namespace Drupal\ymca_retention\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides an instant win block.
 *
 * @Block(
 *   id = "retention_winners_intro_block",
 *   admin_label = @Translation("[YMCA Retention] Winners intro"),
 *   category = @Translation("YMCA Blocks")
 * )
 */
class WinnersIntro extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#theme' => 'ymca_retention_winners_intro',
      '#content' => [],
      '#attached' => [
        'library' => [
          'ymca_retention/angular-app',
        ],
      ],
    ];
  }

}
