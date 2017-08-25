<?php

namespace Drupal\ymca_retention\Plugin\QueueWorker;

use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\openy_campaign\Entity\MemberCheckIn;

/**
 * Updates member checkins for retention campaign.
 *
 * @QueueWorker(
 *   id = "openy_campaign_updates_member_visits",
 *   title = @Translation("Updates member visits for retention campaign"),
 *   cron = {"time" = 60}
 * )
 */
class MemberCheckinWorkerUpdate extends QueueWorkerBase {

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
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    // Get info from CRM
    /** @var $client \Drupal\openy_campaign\CRMClientInterface */
    $client = \Drupal::getContainer()->get('openy_campaign.client_factory')->getClient();

    $results = $client->getVisitsBatch($data['items'], $data['date_from'], $data['date_to']);
    if (!empty($results->ErrorMessage)) {
      $logger = \Drupal::logger('openy_campaign_queue');
      $logger->alert('Could not retrieve visits information for members for batch operation');
      return;
    }

    foreach ($results->FacilityVisitCustomerRecord as $item) {
      if (!isset($item->TotalVisits) || $item->TotalVisits == 0) {
        continue;
      }
      $member_id = array_search($item->MasterCustomerId, $data['items']);
      /** @var \DateTime $dateFrom */
      $dateFrom = $data['date_from'];
      $timestampFrom = $dateFrom->getTimestamp();

      $checkin_ids = \Drupal::entityQuery('openy_campaign_member_checkin')
        ->condition('member', $member_id)
        ->condition('date', $timestampFrom)
        ->execute();

      // Verify checkins for the day.
      if (!empty($checkin_ids)) {
        continue;
      }

      // Create check-in record.
      $checkin = MemberCheckIn::create([
        'date' => $timestampFrom,
        'checkin' => TRUE,
        'member' => $member_id,
      ]);
      $checkin->save();
    }
  }

}
