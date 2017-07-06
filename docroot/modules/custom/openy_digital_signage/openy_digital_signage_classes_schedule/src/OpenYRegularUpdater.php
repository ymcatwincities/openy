<?php

namespace Drupal\openy_digital_signage_classes_schedule;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Queue\QueueFactory;

/**
 * Defines a regular updater service.
 *
 * @ingroup openy_digital_signage_classes_schedule
 */
class OpenYRegularUpdater implements OpenYRegularUpdaterInterface {

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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger channel factory.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LoggerChannelFactoryInterface $logger_factory) {
    $this->entityTypeManager = $entity_type_manager;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function removeOldSessions() {
    // build a date for previous day.
    $date = new \DateTime();
    $date->setTime(0, 0, 0);
    $date->sub(new \DateInterval('P1D'));

    // Get list of ids to delete.
    $ids = \Drupal::entityQuery('openy_ds_classes_session')
      ->condition('date', $date->format('Y-m-d'), '<=')
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
  }

}
