<?php

namespace Drupal\ymca_retention\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\ymca_retention\Entity\Member;
use Drupal\ymca_retention\Entity\Winner;

/**
 * Class WinnersController.
 */
class WinnersController extends ControllerBase {

  /**
   * Draw YMCA Retention campaign winners.
   */
  public function drawWinners() {
    $operations = [
      [[get_class($this), 'processBatch'], []],
    ];
    $batch = [
      'title' => t('Drawing winners'),
      'operations' => $operations,
      'finished' => [get_class($this), 'finishBatch'],
    ];
    batch_set($batch);

    $url = Url::fromRoute('view.ymca_retention_winners.ymca_retention_winners');
    return batch_process($url);
  }

  /**
   * Processes the drawing winners batch.
   *
   * @param array $context
   *   The batch context.
   */
  public static function processBatch(&$context) {
    if (empty($context['sandbox'])) {
      // Remove all winners.
      $winner_ids = \Drupal::entityQuery('ymca_retention_winner')
        ->execute();
      $storage = \Drupal::entityTypeManager()->getStorage('ymca_retention_winner');
      $winners = $storage->loadMultiple($winner_ids);
      $storage->delete($winners);

      $context['sandbox']['progress'] = 0;

      /** @var \Drupal\ymca_retention\LeaderboardManager $service */
      $service = \Drupal::service('ymca_retention.leaderboard_manager');
      $branches = $service->getMemberBranches();

      $context['sandbox']['branches'] = $branches;
      $context['sandbox']['max'] = count($branches);
    }

    $branch_id = $context['sandbox']['branches'][$context['sandbox']['progress']];

    // Select winner candidates - all with the same or better result as the
    // 12th place in each track.
    $tracks = [
      'total_visits' => 'visits',
      'activity_track_swimming' => 'swimming',
      'activity_track_fitness' => 'fitness',
      'activity_track_groupx' => 'groupex',
    ];
    $candidates = [];

    // Count registered members in the branch.
    $count = \Drupal::entityQueryAggregate('ymca_retention_member')
      ->condition('branch', $branch_id)
      ->condition('is_employee', FALSE)
      ->aggregate('id', 'COUNT')
      ->execute();
    $limit = min($count[0]['id_count'], 12);

    foreach ($tracks as $field => $track) {
      // Determine limit-th member.
      $member_ids = \Drupal::entityQuery('ymca_retention_member')
        ->condition('branch', $branch_id)
        ->condition('is_employee', FALSE)
        ->sort($field, 'DESC')
        ->range($limit - 1, 1)
        ->execute();
      if (empty($member_ids)) {
        continue;
      }
      $member_id = reset($member_ids);
      $member = Member::load($member_id);
      $member_ids = \Drupal::entityQuery('ymca_retention_member')
        ->condition('branch', $branch_id)
        ->condition('is_employee', FALSE)
        ->condition($field, $member->get($field)->value, '>=')
        ->sort($field, 'DESC')
        ->execute();
      $members = Member::loadMultiple($member_ids);
      /** @var Member $member */
      foreach ($members as $member) {
        $candidates[$track][$member->getId()] = $member->get($field)->value;
      }
    }

    // Draw candidates ranking - preliminary winners.
    $preliminary_winners = [];
    foreach ($tracks as $track) {
      $count = count($candidates[$track]);
      for ($i = 1; $i <= $count; $i++) {
        // Select candidates for this place.
        $candidates_place = [];
        $cut_value = 0;
        foreach ($candidates[$track] as $id => $value) {
          if ($value >= $cut_value) {
            $candidates_place[$id] = $value;
            $cut_value = $value;
          }
          else {
            break;
          }
        }

        // Select random winner from candidates for this place.
        $winner_id = array_rand($candidates_place, 1);
        $preliminary_winners[$track][] = $winner_id;
        // Remove winner from candidates.
        unset($candidates[$track][$winner_id]);
      }
    }

    $winners = $preliminary_winners;
    // Filter preliminary winners so that winner gets only one best possible
    // reward. First filter by place, then by track.
    foreach ([0, 1, 2] as $place) {
      foreach ($tracks as $track) {
        $id_to_remove = $winners[$track][$place];

        foreach ($tracks as $track_to_filter) {
          if ($track === $track_to_filter) {
            continue;
          }

          $key_to_remove = array_search($id_to_remove, $winners[$track_to_filter]);
          if ($key_to_remove === FALSE) {
            continue;
          }

          unset($winners[$track_to_filter][$key_to_remove]);
          $winners[$track_to_filter] = array_values($winners[$track_to_filter]);
        }
      }
    }

    // Save winners - create winner entities.
    foreach ($tracks as $track) {
      $place = 1;
      foreach ($winners[$track] as $member_id) {
        $winner = Winner::create([
          'branch' => $branch_id,
          'member' => $member_id,
          'track' => $track,
          'place' => $place,
        ]);
        $winner->save();
        $context['results'][] = $member_id;

        if ($place >= 3) {
          break;
        }
        $place++;
      }
    }

    $context['sandbox']['progress']++;

    if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
    }
  }

  /**
   * Finish batch.
   */
  public static function finishBatch($success, $results, $operations) {
    if ($success) {
      $message = \Drupal::translation()->formatPlural(count($results), 'Created one winner.', 'Created @count winners.');
    }
    else {
      $message = t('Finished with an error.');
    }
    drupal_set_message($message);
  }

}
