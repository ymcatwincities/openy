<?php

namespace Drupal\ymca_retention;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Queue\QueueFactory;

/**
 * Defines a regular updater service.
 */
class RegularUpdater implements RegularUpdaterInterface {

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * The queue factory.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * Creates a new RegularUpdater.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger channel factory.
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   *   The queue factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, LoggerChannelFactoryInterface $logger_factory, QueueFactory $queue_factory) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->loggerFactory = $logger_factory;
    $this->queueFactory = $queue_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function isAllowed($allow_often = FALSE) {
    $config = $this->configFactory->getEditable('ymca_retention.cron_settings');
    $last_run = new \DateTime();
    $last_run->setTimestamp($config->get('last_run'));
    $diff = $last_run->diff(new \DateTime());
    $diff_hrs = $diff->d * 24 + $diff->h;
    // Check if cron was run less then 12 hrs ago.
    if ($diff_hrs < 12 && !$allow_often) {
      return FALSE;
    }

    // Get campaign dates settings.
    $settings = $this->configFactory->get('ymca_retention.general_settings');
    $date_open = new \DateTime($settings->get('date_campaign_open'));
    $date_close = new \DateTime($settings->get('date_campaign_close'));
    // Add 1 day to closing date to get visits for the last day.
    $date_close->add(new \DateInterval('P1D'));
    $current_date = new \DateTime();

    return $current_date >= $date_open && $current_date <= $date_close;
  }

  /**
   * {@inheritdoc}
   */
  public function runUpdate() {
    // @todo this method is outdated.
    $cron_config = $this->configFactory->getEditable('ymca_retention.cron_settings');

    // Get campaign dates settings.
    $settings = $this->configFactory->get('ymca_retention.general_settings');
    $from = $settings->get('date_campaign_open');
    $to = $settings->get('date_campaign_close');

    // Load members.
    $members = $this->entityTypeManager->getStorage('ymca_retention_member')
      ->loadMultiple();

    /** @var \Drupal\ymca_retention\Entity\Member $member */
    foreach ($members as $member) {
      $stored_visits = $member->getVisits();
      $membership_id = $member->getPersonifyId();

      // Get information about number of checkins in period of the campaign.
      $result = PersonifyApi::getPersonifyVisitCountByDate($membership_id, $from, $to);
      if (!empty($result->ErrorMessage)) {
        $this->loggerFactory->get('ymca_retention')
          ->error('Could not retrieve visits count for member %member_id', [
            '%member_id' => $membership_id,
          ]);
        continue;
      }

      // Store updated visits counter.
      if ($result->TotalVisits != $stored_visits) {
        $member->setVisits($result->TotalVisits);
        $member->save();
      }
    }

    $cron_config->set('last_run', time())->save();
  }

  /**
   * Create Queue.
   *
   * @param int $from
   *   Timestamp.
   * @param int $to
   *   Timestamp.
   */
  public function createQueue($from, $to) {
    $queue = $this->queueFactory->get('ymca_retention_updates_member_visits');
    $members = $this->entityTypeManager->getStorage('ymca_retention_member')
      ->loadMultiple();

    /** @var \Drupal\ymca_retention\Entity\Member $member */
    $chunks = array_chunk($members, 100);
    $data = [
      'date_from' => $from,
      'date_to' => $to,
    ];
    foreach ($chunks as $chunk) {
      $data['items'] = [];
      foreach ($chunk as $member) {
        $data['items'][] = [
          'id' => (int) $member->getId(),
        ];
      }
      $queue->createItem($data);
    }
    $cron_config = $this->configFactory->getEditable('ymca_retention.cron_settings');
    $cron_config->set('last_run', time())->save();
  }

}
