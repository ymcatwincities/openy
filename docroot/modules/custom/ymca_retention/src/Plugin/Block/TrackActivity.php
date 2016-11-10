<?php

namespace Drupal\ymca_retention\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a block with form for tracking activity.
 *
 * @Block(
 *   id = "retention_track_activity_block",
 *   admin_label = @Translation("[YMCA Retention] Track activity"),
 *   category = @Translation("YMCA Blocks")
 * )
 */
class TrackActivity extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    /** @var \Drupal\ymca_retention\ActivityManager $service */
    $service = \Drupal::service('ymca_retention.activity_manager');
    $dates = $service->getDates();
    $activity_groups = $service->getActivityGroups();
    $member_activities_url = $service->getUrl();

    return [
      '#theme' => 'ymca_retention_track_activity',
      '#attached' => [
        'library' => [
          'ymca_retention/activity',
        ],
        'drupalSettings' => [
          'ymca_retention' => [
            'activity' => [
              'dates' => $dates,
              'activity_groups' => $activity_groups,
              'member_activities' => $member_activities_url,
            ],
          ],
        ],
      ],
    ];
  }

}
