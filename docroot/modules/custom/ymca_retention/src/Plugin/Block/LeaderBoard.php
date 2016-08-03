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

    // Get retention settings.
    $settings = \Drupal::config('ymca_retention.general_settings');
    $date_open = new \DateTime($settings->get('date_reporting_open'));
    $current_date = new \DateTime();

    if ($current_date < $date_open) {
      /** @var \Drupal\Core\Datetime\DateFormatter $date_formatter */
      $date_formatter = \Drupal::service('date.formatter');

      $description = $this->t('Leaderboard tracking will begin on @date_open once the games have begun. It will be updated approximately every six hours.',
        [
          '@date_open' => $date_formatter->format($date_open->getTimestamp(), 'custom', 'F j'),
        ]);
      $active = FALSE;
    }
    else {
      $description = $this->t('Leaderboard updated approximately every six hours.');
      $active = TRUE;
    }

    return [
      '#theme' => 'ymca_retention_leaderboard',
      '#description' => $description,
      '#attached' => [
        'library' => [
          'ymca_retention/leaderboard',
        ],
        'drupalSettings' => [
          'ymca_retention' => [
            'leaderboard' => [
              'leaderboard_url_pattern' => Url::fromRoute('ymca_retention.leaderboard_json', ['branch_id' => '0000'])->toString(),
              'locations' => $locations,
              'active' => $active,
            ],
          ],
        ],
      ],
    ];
  }

}
