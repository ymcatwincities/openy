<?php

namespace Drupal\openy_digital_signage_schedule;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\openy_digital_signage_schedule\Entity\OpenYSchedule;

/**
 * Class OpenYScheduleManager.
 */
class OpenYScheduleManager implements OpenYScheduleManagerInterface {

  /**
   * Logger channel definition.
   */
  const CHANNEL = 'openy_digital_signage';

  /**
   * Collection name.
   */
  const STORAGE = 'openy_digital_signage_schedule';

  /**
   * The query factory.
   *
   * @var QueryFactory
   */
  protected $entityQuery;

  /**
   * The entity type manager.
   *
   * @var EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The entity storage.
   *
   * @var EntityStorageInterface
   */
  protected $storage;

  /**
   * LoggerChannelFactoryInterface definition.
   *
   * @var LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * Constructor.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, QueryFactory $entity_query, LoggerChannelFactoryInterface $logger_factory) {
    $this->entityQuery = $entity_query;
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger_factory->get(self::CHANNEL);
    $this->storage = $this->entityTypeManager->getStorage(self::STORAGE);
  }

  /**
   * {@inheritdoc}
   */
  public function dummy() {
    // Intentionally empty.
  }

  /**
   * {@inheritdoc}
   */
  public function getUpcomingScreenContents(OpenYSchedule $schedule, $timespan, $now = NULL) {
    if (!$now) {
      $now = time();
    }

    $query = $this->entityQuery->get('openy_digital_signage_sch_item');
    $query->condition('schedule', $schedule->id());
    $entity_ids = $query->execute();

    if (!$entity_ids) {
      return [];
    }

    $schedule_items = $this->entityTypeManager
      ->getStorage('openy_digital_signage_sch_item')
      ->loadMultiple($entity_ids);

    $schedule = [];
    $now_formatted = date('H:i:s', $now);
    $today = date('Y-m-d', $now);
    $tomorrow = date('Y-m-d', strtotime($today . ' +1day'));
    foreach ($schedule_items as $schedule_item) {
      $from_ts = strtotime($schedule_item->get('time_slot')->value . 'z');
      $to_ts = strtotime($schedule_item->get('time_slot')->end_value . 'z');
      $from_time = date('H:i:s', $from_ts);
      $to_time = date('H:i:s', $to_ts);
      $date = $today;
      if ($to_time < $now_formatted) {
        $date = $tomorrow;
      }
      $from = $date . 'T' . $from_time;
      $to = $date . 'T' . $to_time;
      $from_ts = strtotime($from);
      $to_ts = strtotime($to);
      $schedule[$from] = [
        'item' => $schedule_item,
        'from' => $from,
        'to' => $to,
        'from_ts' => $from_ts,
        'to_ts' => $to_ts,
      ];

    }

    ksort($schedule);

    return $schedule;
  }

}
