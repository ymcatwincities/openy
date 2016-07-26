<?php

namespace Drupal\ymca_retention\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\ymca_retention\Entity\Member;
use Drupal\ymca_retention\PersonifyApi;

/**
 * Updates member visits for retention campaign.
 *
 * @QueueWorker(
 *   id = "ymca_retention_updates_member_visits",
 *   title = @Translation("Updates member visits for retention campaign"),
 *   cron = {"time" = 60}
 * )
 */
class MemberVisitsWorkerUpdate extends QueueWorkerBase {

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    // Get information about number of checkins in period of the campaign.
    $result = PersonifyApi::getPersonifyVisitCountByDate($data['membership_id'], $data['form'], $data['to']);
    if (!empty($result->ErrorMessage)) {
      $logger = \Drupal::logger('ymca_retention_queue');
      $logger->alert('Could not retrieve visits count for member %member_id', [
        '%member_id' => $data['membership_id'],
      ]);
      return;
    }
    $member = Member::load($data['id']);
    // Store updated visits counter.
    if ($result->TotalVisits != $member->getVisits()) {
      $member->setVisits($result->TotalVisits);
      $member->save();
    }
  }

}
