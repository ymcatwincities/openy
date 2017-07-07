<?php

namespace Drupal\openy_digital_signage_classes_schedule;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Defines a cron service for classes schedule.
 *
 * @ingroup openy_digital_signage_classes_schedule
 */
class OpenYClassesScheduleCron implements OpenYClassesScheduleCronInterface {

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
  public function removeOldSessions() {
    $config = $this->configFactory->getEditable('openy_digital_signage_classes_schedule.cron_settings');
    $current_time = new \DateTime();
    $last_run = new \DateTime();
    $last_run->setTimestamp($config->get('last_run'));
    $diff = $last_run->diff($current_time);
    // Check if cron was run less days than specified in settings.
    if ($diff->days < $config->get('period_days')) {
      return;
    }

    // Verify start time.
    $allowed_time = new \DateTime();
    $allowed_time->setTime($config->get('start_run_hour'), 0);
    if ($allowed_time < $current_time) {
      return;
    }

    // Build a date for previous day.
    $date = new \DateTime();
    $date->setTime(0, 0, 0);
    $date->sub(new \DateInterval('P' . $config->get('period_days') . 'D'));

    // Get list of ids to delete.
    $ids = \Drupal::entityQuery('openy_ds_classes_session')
      ->condition('date_time__value', $date->format('Y-m-d'), '<=')
      ->execute();
    if (empty($ids)) {
      return;
    }

    // Load session entities.
    $sessions = $this->entityTypeManager->getStorage('openy_ds_classes_session')
      ->loadMultiple($ids);

    // Delete session entities.
    $this->entityTypeManager->getStorage('openy_ds_classes_session')
      ->delete($sessions);

    // Save last run.
    $config->set('last_run', time())->save();
  }

}
