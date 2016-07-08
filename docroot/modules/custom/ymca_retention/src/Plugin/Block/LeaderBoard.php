<?php

namespace Drupal\ymca_retention\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;

/**
 * Provides a leader board block.
 *
 * @Block(
 *   id = "retention_leaderboard_block",
 *   admin_label = @Translation("YMCA retention leaderboard block"),
 *   category = @Translation("YMCA Blocks")
 * )
 */
class Leaderboard extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    // TODO: somehow build locations select.
    $locations = \Drupal::entityQuery('mapping')
      ->condition('type', 'location')
      ->execute();

    $locations = [
      [
        'branch_id' => 0,
        'name' => 'Select location...',
      ],
      [
        'branch_id' => 14,
        'name' => 'Location 14',
      ],
      [
        'branch_id' => 26,
        'name' => 'Location 26',
      ],
    ];

    return [
      '#theme' => 'ymca_retention_leaderboard',
      '#attached' => [
        'library' => [
          'ymca_retention/angular',
          'ymca_retention/leaderboard',
        ],
        'drupalSettings' => [
          'ymca_retention' => [
            'leaderboard' => Url::fromRoute('ymca_retention.leaderboard_json', ['branch_id' => '0000'])->toString(),
            'locations' => $locations,
          ],
        ],
      ],
    ];
  }

}
