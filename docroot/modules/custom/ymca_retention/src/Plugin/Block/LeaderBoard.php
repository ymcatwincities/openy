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
    /** @var \Drupal\ymca_retention\LeaderboardManager $service */
    $service = \Drupal::service('ymca_retention.leaderboard_manager');
    $locations = $service->getLocations();

    return [
      '#theme' => 'ymca_retention_leaderboard',
      '#locations' => $locations,
      '#attached' => [
        'library' => [
          'ymca_retention/leaderboard',
        ],
        'drupalSettings' => [
          'ymca_retention' => [
            'leaderboard' => [
              'leaderboard_url_pattern' => Url::fromRoute('ymca_retention.leaderboard_json', ['branch_id' => '0000'])->toString(),
              'locations' => $locations,
            ],
          ],
        ],
      ],
    ];
  }

}
