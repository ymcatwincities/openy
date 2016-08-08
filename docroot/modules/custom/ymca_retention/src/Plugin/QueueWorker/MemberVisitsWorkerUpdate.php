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
   * Campaign start date.
   *
   * @var string
   */
  protected $dateFrom;

  /**
   * Campaign end date.
   *
   * @var string
   */
  protected $dateEnd;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    // Get campaign dates settings.
    $settings = \Drupal::config('ymca_retention.general_settings');
    $this->dateFrom = $settings->get('date_campaign_open');
    $this->dateTo = $settings->get('date_campaign_close');
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data) {
    // Load member.
    $member = Member::load($data['id']);

    // Get information about number of checkins in period of the campaign.
    $result = PersonifyApi::getPersonifyVisitCountByDate($member->getMemberId(), $this->dateFrom, $this->dateTo);
    if (!empty($result->ErrorMessage)) {
      $logger = \Drupal::logger('ymca_retention_queue');
      $logger->alert('Could not retrieve visits count for member %member_id', [
        '%member_id' => $member->getMemberId(),
      ]);
      return;
    }
    $current_visits = (int) $member->getVisits();

    // Store updated visits counter.
    if (isset($result->TotalVisits) && ((int) $result->TotalVisits != $current_visits)) {
      $member->setVisits($result->TotalVisits);
      $member->save();
    }
    elseif (!is_numeric($current_visits)) {
      // Some users does not have a value, we have to set 0 as default for them.
      $member->setVisits(0);
      $member->save();
    }
  }

}
