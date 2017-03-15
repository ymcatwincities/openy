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
      [[get_class($this), 'processSpring2017Batch'], []],
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

    foreach ($tracks as $field => $track) {
      // Count registered members in the branch qualified to win the track.
      $query = \Drupal::entityQueryAggregate('ymca_retention_member')
        ->condition('branch', $branch_id)
        ->condition('is_employee', FALSE);
      if ($track == 'visits') {
        $query->addTag('ymca_retention_visit_goal');
      }
      $count = $query->aggregate('id', 'COUNT')
        ->execute();
      $limit = min($count[0]['id_count'], 12);
      if ($limit == 0) {
        continue;
      }

      // Determine the limit-th member.
      $query = \Drupal::entityQuery('ymca_retention_member')
        ->condition('branch', $branch_id)
        ->condition('is_employee', FALSE);
      if ($track == 'visits') {
        $query->addTag('ymca_retention_visit_goal');
      }
      $member_ids = $query->sort($field, 'DESC')
        ->range($limit - 1, 1)
        ->execute();
      if (empty($member_ids)) {
        continue;
      }
      $member_id = reset($member_ids);
      $member = Member::load($member_id);

      // Select candidates.
      $query = \Drupal::entityQuery('ymca_retention_member')
        ->condition('branch', $branch_id)
        ->condition('is_employee', FALSE);
      if ($track == 'visits') {
        $query->addTag('ymca_retention_visit_goal');
      }
      $member_ids = $query->condition($field, $member->get($field)->value, '>=')
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
   * Processes the drawing winners batch for Spring2017 campaign.
   *
   * @param array $context
   *   The batch context.
   */
  public static function processSpring2017Batch(&$context) {
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
      // Get all non excluded branches.
      $branches = $service->getMemberBranches();

      $context['sandbox']['branches'] = $branches;
      $context['sandbox']['max'] = count($branches);
    }

    $branch_id = $context['sandbox']['branches'][$context['sandbox']['progress']];

    $query = \Drupal::entityQuery('ymca_retention_member')
      ->condition('branch', $branch_id)
      ->condition('is_employee', FALSE);
    $member_ids = $query->execute();

    $nominations = [
      '$100' => 1,
      '$25' => 3,
      '$5' => 30,
    ];
    $count = 0;
    $place = 1;
    foreach ($nominations as $nomination => $quantity) {
      for ($i = 0; $i < $quantity; $i++) {
        if (empty($member_ids)) {
          break;
        }
        $member_id = self::selectOneMember($member_ids);
        if (!$member_id) {
          break;
        }
        $winner = Winner::create([
          'branch' => $branch_id,
          'member' => $member_id,
          'place' => $place,
        ]);
        $winner->save();
        $count++;
        $context['results'][] = $member_id;
        unset($member_ids[$member_id]);
      }
      $place++;
    }

    $message = \Drupal::translation()->formatPlural($count, 'Created one winner for branch ', 'Created @count winners for branch ') . $branch_id;
    drupal_set_message($message);
    $context['sandbox']['progress']++;

    if ($context['sandbox']['progress'] != $context['sandbox']['max']) {
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['max'];
    }
  }

  private static function selectOneMember($member_ids) {
    $query = \Drupal::entityQuery('ymca_retention_member_bonus')
      ->condition('member', $member_ids, 'IN');
    $member_bonuses_ids = $query->execute();

    if (empty($member_bonuses_ids)) {
      return FALSE;
    }

    $member_bonus_id = array_rand($member_bonuses_ids);
    $member_bonus = MemberBonus::load($member_bonus_id);

    return $member_bonus->getMember();
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
