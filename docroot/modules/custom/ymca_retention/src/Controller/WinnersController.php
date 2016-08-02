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

    // Select 12 members from the branch.
    $member_ids = \Drupal::entityQuery('ymca_retention_member')
      ->condition('branch', $branch_id)
      ->condition('is_employee', FALSE)
      ->range(NULL, 12)
      ->execute();
    $members = Member::loadMultiple($member_ids);

    // Create winners from selected members.
    $member = current($members);
    foreach (['visits', 'swimming', 'fitness', 'groupx'] as $track) {
      foreach ([1, 2, 3] as $place) {
        /** @var Member $member */
        if (!$member) {
          break 2;
        }
        $winner = Winner::create([
          'branch' => $branch_id,
          'member' => $member->getId(),
          'track' => $track,
          'place' => $place,
        ]);
        $winner->save();
        $context['results'][] = $member->getId();

        $member = next($members);
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
