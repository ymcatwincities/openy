<?php

namespace Drupal\ymca_retention\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\ymca_retention\Entity\Member;
use Drupal\ymca_retention\Entity\MemberBonus;
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
    $winners = [];
    $tracks = [
      YMCA_RETENTION_ACTIVITY_SWIMMING => 'swimming',
      YMCA_RETENTION_ACTIVITY_FITNESS => 'fitness',
      YMCA_RETENTION_ACTIVITY_GROUPX => 'groupx',
      YMCA_RETENTION_ACTIVITY_COMMUNITY => 'community',
    ];

    foreach ($tracks as $activity_id => $track) {
      // This subquery gets all activities filtered by an activity group and
      // a branch and groupped by member id and a date the activity was taken.
      // This will help us to get actual entries (winning chances) member has.
      // Daily a member can get up to 4 entries (equals to a number of activity
      // categories - 4). So if user tracks more than 1 activity for the
      // swimming category it will be still counted as 1 entry.
      $subquery = \Drupal::database()
        ->select('ymca_retention_member', 'M')
        ->fields('M', ['id', 'total_visits']);

      $subquery->leftJoin('ymca_retention_member_activity', 'MA', 'M.id = MA.member');
      $subquery->leftJoin('taxonomy_term_data', 'AT', 'MA.activity_type = AT.tid');
      $subquery->leftJoin('taxonomy_term_hierarchy', 'H', 'AT.tid = H.tid');
      $subquery->leftJoin('taxonomy_term__field_retention_activity_id', 'AID', 'AID.entity_id = H.parent');

      // We're only getting user who have reached a goal.
      $subquery->where('total_visits >= visit_goal')
        ->condition('is_employee', FALSE)
        ->condition('branch', $branch_id)
        ->condition('AID.field_retention_activity_id_value', $activity_id)
        ->groupBy('M.id, MA.timestamp, M.total_visits');

      // Calculate a number of entries we get from the subquery.
      $query = \Drupal::database()
        ->select($subquery, 'T')
        ->fields('T', ['id']);

      $query->addExpression('COUNT(T.id)', 'total_entries');
      $query->groupBy('T.id');

      $members = $query->execute()->fetchAllKeyed();

      // Randomly choose winners from the candidates. There are 12 candidates
      // from each track since we need to be prepared if there are intersected
      // winners from different tracks.
      for ($i = 0; $i < 12; $i++) {
        if (empty($members)) {
          break;
        }

        $entries_sum = 0;
        $entries_total = array_sum($members);
        $winning_entry = mt_rand(1, $entries_total);

        foreach ($members as $member_id => $member_entries) {
          if ($winning_entry > $entries_sum && $winning_entry <= ($member_entries + $entries_sum)) {
            unset($members[$member_id]);
            $winners[$track][] = $member_id;
            break;
          }

          $entries_sum += $member_entries;
        }
      }
    }

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

        if ($place++ >= 3) {
          break;
        }
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
