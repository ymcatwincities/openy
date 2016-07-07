<?php

namespace Drupal\ymca_retention\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\taxonomy\TermStorage;
use Drupal\ymca_retention\Entity\Member;

/**
 * Provides a leader board block.
 *
 * @Block(
 *   id = "retention_leader_board_block",
 *   admin_label = @Translation("YMCA retention leader board block"),
 *   category = @Translation("YMCA Blocks")
 * )
 */
class LeaderBoard extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    // TODO: somehow build locations select.
    $locations = \Drupal::entityQuery('mapping')
      ->condition('type', 'location')
      ->execute();

    $branch_id = 0;

    // Prepare taxonomy data.
    /** @var TermStorage $term_storage */
    $term_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
    $parents = $term_storage->loadTree('ymca_retention_activities', 0, 1);
    foreach ($parents as $parent) {
      $parent->children_ids = [];
      $children = $term_storage->loadTree('ymca_retention_activities', $parent->tid, 1);
      foreach ($children as $child) {
        $parent->children_ids[] = $child->tid;
      }
    }

    $member_ids = \Drupal::entityQuery('ymca_retention_member')
      ->condition('branch', $branch_id)
      ->execute();
    $members = \Drupal::entityTypeManager()
      ->getStorage('ymca_retention_member')
      ->loadMultiple($member_ids);

    $data = [];
    /** @var Member $member */
    foreach ($members as $rank => $member) {
      $activities = [];
      foreach ($parents as $parent) {
        $activities_ids = \Drupal::entityQuery('ymca_retention_member_activity')
          ->condition('member', $member->id())
          ->condition('activity_type', $parent->children_ids, 'IN')
          ->execute();
        $activities[] = count($activities_ids);
      }

      $data[] = [
        'rank' => $rank,
        'first_name' => $member->getFirstName(),
        'last_name' => $member->getLastName(),
        'membership_id' => substr($member->getMemberId(), -4),
        'activities' => $activities,
        'visits' => $member->getVisits(),
      ];
    }

    $json = json_encode($data);
    $directory = 'public://ymca_retention';
    file_prepare_directory($directory, FILE_CREATE_DIRECTORY);
    $filepath = $directory . '/leaderboard.' . $branch_id . '.js';
    file_unmanaged_save_data($json, $filepath, FILE_EXISTS_REPLACE);

    $file_pattern = $directory . '/leaderboard.branch_id.js';
    return [
      '#theme' => 'ymca_retention_leader_board',
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
