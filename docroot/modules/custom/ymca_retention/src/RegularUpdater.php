<?php

namespace Drupal\ymca_retention;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

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
   * Creates a new RegularUpdater.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger channel factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager, LoggerChannelFactoryInterface $logger_factory) {
    $this->configFactory = $config_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function isAllowed() {
    $config = $this->configFactory->getEditable('ymca_retention.cron_settings');
    $last_run = $config->get('last_run');

    // Check if cron was run today.
    return date('D/M/Y') != date('D/M/Y', $last_run);
  }

  /**
   * {@inheritdoc}
   */
  public function runUpdate() {
    $cron_config = $this->configFactory->getEditable('ymca_retention.cron_settings');

    // Get campaign dates settings.
    $settings = $this->configFactory->get('ymca_retention.general_settings');
    $from = $settings->get('date_registration_open');
    $to = $settings->get('date_registration_close');

    // Load members.
    $members = $this->entityTypeManager
      ->getStorage('ymca_retention_member')
      ->loadMultiple();

    /** @var \Drupal\ymca_retention\Entity\Member $member */
    foreach ($members as $member) {
      $stored_visits = $member->getVisits();
      $membership_id = $member->getMemberId();

      // Get information about number of checkins in period of the campaign.
      $result = PersonifyApi::getPersonifyVisitCountByDate($membership_id, $from, $to);
      if (!empty($result->ErrorMessage)) {
        $this->loggerFactory
          ->get('ymca_retention')
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
}
