<?php

namespace Drupal\ymca_retention\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\ymca_retention\Entity\Member;
use Drupal\ymca_retention\Entity\MemberCheckIn;
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
   * Campaign start date.
   *
   * @var \DateTime
   */
  protected $dateOpen;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    // Get campaign dates settings.
    $settings = \Drupal::config('ymca_retention.general_settings');
    try {
      $this->dateOpen = new \DateTime($settings->get('date_campaign_open'));
    }
    catch (\Exception $e) {
      $this->dateOpen = new \DateTime();
      $this->dateOpen->setTime(0, 0, 0);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    $list_ids = [];
    foreach ($data['items'] as $item) {
      // Load member.
      $member = Member::load($item['id']);
      $list_ids[$item['id']] = $member->getPersonifyId();
    }
    // Date from.
    $date_from = new \DateTime();
    $date_from->setTimestamp($data['date']);
    $date_from->setTime(0, 0, 0);
    $date_from->sub(new \DateInterval('P1D'));
    if ($date_from < $this->dateOpen) {
      $date_from = $this->dateOpen;
    }

    // Date To.
    $date_to = clone $date_from;
    $date_to->setTime(23, 59, 59);

    // Get information about number of checkins in period of the campaign.
    $results = PersonifyApi::getPersonifyVisitsBatch($list_ids, $date_from, $date_to);
    if (!empty($results->ErrorMessage)) {
      $logger = \Drupal::logger('ymca_retention_queue');
      $logger->alert('Could not retrieve visits information for members for batch operation');
      return;
    }
    foreach ($results->FacilityVisitCustomerRecord as $item) {
      if (!isset($item->TotalVisits) || $item->TotalVisits == 0) {
        continue;
      }
      // Create check-in record.
      $checkin = MemberCheckIn::create([
        'created' => $date_from->getTimestamp(),
        'checkin' => TRUE,
        'member' => array_search($item->MasterCustomerId, $list_ids),
      ]);
      $checkin->save();
    }
  }

}
