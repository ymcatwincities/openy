<?php

namespace Drupal\ymca_retention\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\ymca_retention\Entity\Member;
use Drupal\ymca_retention\Entity\Winner;

/**
 * Provides a winners block.
 *
 * @Block(
 *   id = "retention_winners_block",
 *   admin_label = @Translation("[YMCA Retention] Winners"),
 *   category = @Translation("YMCA Blocks")
 * )
 */
class Winners extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    /** @var \Drupal\ymca_retention\LeaderboardManager $service */
    $service = \Drupal::service('ymca_retention.leaderboard_manager');
    $locations = $service->getLocationsList(FALSE);

    // Select all winners.
    $winner_ids = \Drupal::entityQuery('ymca_retention_winner')
      ->execute();
    $winners = Winner::loadMultiple($winner_ids);

    $winners_list = [];
    /** @var Winner $winner */
    foreach ($winners as $winner) {
      $branch_id = $winner->get('branch')->value;
      $place = $winner->get('place')->value;
      $track = $winner->get('track')->value;
      /** @var Member $member */
      $member = $winner->member->entity;
      $winners_list[$branch_id][$place][$track] = [
        'first_name' => $member->getFirstName(),
        'last_name' => substr($member->getLastName(), 0, 1),
        'membership_id' => substr($member->getMemberId(), -4),
      ];
    }

    $settings = \Drupal::config('ymca_retention.general_settings');
    $current_date = new \DateTime();
    $date_winners_announcement = new \DateTime($settings->get('date_winners_announcement'));
    /** @var \Drupal\Core\Datetime\DateFormatter $date_formatter */
    $date_formatter = \Drupal::service('date.formatter');
    $description = $this->t('Thank you for playing Y Games 2016. Winners will be announced by @date.',
      [
        '@date' => $date_formatter->format($date_winners_announcement->getTimestamp(), 'custom', 'F j'),
      ]);

    return [
      '#theme' => 'ymca_retention_winners',
      '#description' => $description,
      '#attached' => [
        'library' => [
          'ymca_retention/winners',
        ],
        'drupalSettings' => [
          'ymca_retention' => [
            'winners' => [
              'locations' => $locations,
              'winners' => $winners_list,
              'winners_show' => $current_date > $date_winners_announcement,
            ],
          ],
        ],
      ],
    ];
  }

}
