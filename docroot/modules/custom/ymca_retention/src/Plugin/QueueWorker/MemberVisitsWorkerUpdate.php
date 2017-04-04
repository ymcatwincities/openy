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
   * Campaign end date.
   *
   * @var \DateTime
   */
  protected $dateClose;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    // Get campaign dates settings.
    $settings = \Drupal::config('ymca_retention.general_settings');
    try {
      $this->dateOpen = new \DateTime($settings->get('date_reporting_open'));
      $this->dateClose = new \DateTime($settings->get('date_reporting_close'));
    }
    catch (\Exception $e) {
      $this->dateOpen = new \DateTime();
      $this->dateOpen->setTime(0, 0, 0);
      $this->dateClose = new \DateTime();
      $this->dateClose->setTime(23, 59, 59);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    // Date From.
    $date_from = new \DateTime();
    $date_from->setTimestamp($data['date_from']);
    if ($date_from < $this->dateOpen) {
      $date_from = $this->dateOpen;
    }
    // Date To.
    $date_to = new \DateTime();
    $date_to->setTimestamp($data['date_to']);
    if ($date_to > $this->dateClose) {
      $date_to = $this->dateClose;
    }

    // Request to Personify.
    $results = PersonifyApi::getPersonifyVisitsBatch($data['items'], $date_from, $date_to);
    if (!empty($results->ErrorMessage)) {
      $logger = \Drupal::logger('ymca_retention_queue');
      $logger->alert('Could not retrieve visits information for members for batch operation');
      return;
    }
    foreach ($results->FacilityVisitCustomerRecord as $item) {
      if (!isset($item->TotalVisits) || $item->TotalVisits == 0) {
        continue;
      }
      $member_id = array_search($item->MasterCustomerId, $data['items']);

      $checkin_ids = \Drupal::entityQuery('ymca_retention_member_checkin')
        ->condition('member', $member_id)
        ->condition('date', $date_from->getTimestamp())
        ->execute();

      // Verify checkins for the day.
      if (!empty($checkin_ids)) {
        continue;
      }

      // Create check-in record.
      $checkin = MemberCheckIn::create([
        'date' => $date_from->getTimestamp(),
        'checkin' => TRUE,
        'member' => $member_id,
      ]);
      $checkin->save();
    }
  }

}
