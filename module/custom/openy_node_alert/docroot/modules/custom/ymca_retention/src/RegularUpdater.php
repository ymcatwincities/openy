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
    $date_open = new \DateTime($settings->get('date_reporting_open'));
    $date_close = new \DateTime($settings->get('date_reporting_close'));
    // Add 1 day to closing date to get visits for the last day.
    $date_close->add(new \DateInterval('P1D'));
    $current_date = new \DateTime();

    return $current_date >= $date_open && $current_date <= $date_close;
  }

  /**
   * {@inheritdoc}
   */
  public function createQueue($from, $to) {
    $queue = $this->queueFactory->get('ymca_retention_updates_member_visits');

    $members = $this->entityTypeManager->getStorage('ymca_retention_member')
      ->loadMultiple();
    $chunks = array_chunk($members, 100);

    $date_from = new \DateTime();
    $date_from->setTimestamp($from)->setTime(0, 0, 0);
    $date_to = new \DateTime();
    $date_to->setTimestamp($to)->setTime(23, 59, 59);

    while ($date_from < $date_to) {
      $data = [
        'date_from' => $date_from->getTimestamp(),
        'date_to' => $date_from->setTime(23, 59, 59)->getTimestamp(),
      ];
      $date_from->add(new \DateInterval('P1D'))->setTime(0, 0, 0);

      foreach ($chunks as $chunk) {
        $data['items'] = [];
        /** @var \Drupal\ymca_retention\Entity\Member $member */
        foreach ($chunk as $member) {
          $data['items'][$member->getId()] = $member->getPersonifyId();
        }
        $queue->createItem($data);
      }
    }
  }

}
