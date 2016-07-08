<?php

namespace Drupal\ymca_retention\Plugin\Block;

use Drupal\Core\Block\BlockBase;

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

    $branch_id = 14;

    /** @var \Drupal\ymca_retention\LeaderboardManager $service */
    $service = \Drupal::service('ymca_retention.leaderboard_manager');
    $data = $service->getLeaderboard($branch_id);

    $json = json_encode($data);
    $directory = 'public://ymca_retention';
    file_prepare_directory($directory, FILE_CREATE_DIRECTORY);
    $filepath = $directory . '/leaderboard.' . $branch_id . '.js';
    file_unmanaged_save_data($json, $filepath, FILE_EXISTS_REPLACE);

    $file_pattern = $directory . '/leaderboard.branch_id.js';
    return [
      '#theme' => 'ymca_retention_leaderboard',
      '#attached' => [
        'library' => [
          'ymca_retention/angular',
          'ymca_retention/leaderboard',
        ],
        'drupalSettings' => [
          'ymca_retention' => [
            'leaderboard' => file_create_url($file_pattern),
          ],
        ],
      ],
    ];
  }

}
