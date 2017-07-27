<?php

namespace Drupal\openy_digital_signage_classes_schedule;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Defines a classes schedule manager.
 *
 * @ingroup openy_digital_signage_classes_schedule
 */
class OpenYClassesScheduleManager implements OpenYClassesScheduleManagerInterface {

  /**
   * Logger channel definition.
   */
  const CHANNEL = 'openy_digital_signage';

  /**
   * Collection name.
   */
  const STORAGE = 'openy_ds_classes_session';

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
  public function getClassesSchedule($period, EntityInterface $location, $room) {
    $datetime = new \DateTime();
    $datetime->setTimezone(new \DateTimeZone('UTC'));
    $datetime->setTimestamp($period['to']);
    $period_to = $datetime->format('c');
    $datetime->setTimestamp($period['from']);
    $period_from = $datetime->format('c');

    $eq = $this->entityQuery->get('openy_ds_classes_session');
    $eq->condition('room_name', $room);
    $eq->condition('field_session_location', $location->id());
    $eq->condition('date_time.value', $period_to, '<=');
    $eq->condition('date_time.end_value', $period_from, '>=');
    $eq->condition('overridden', FALSE);
    $eq->sort('date_time.value');
    $results = $eq->execute();

    $class_sessions = $this->storage->loadMultiple($results);

    $classes = [];
    foreach ($class_sessions as $class_session) {
      $from = strtotime($class_session->date_time->value . 'z');
      $to = strtotime($class_session->date_time->end_value . 'z');
      $classes[] = [
        'from' => $from,
        'to' => $to,
        'trainer' => $class_session->instructor->value,
        'substitute_trainer' => trim($class_session->sub_instructor->value),
        'name' => $class_session->label(),
        'from_formatted' => date('H:ia', $from),
        'to_formatted' => date('H:ia', $to),
      ];
    }

    return $classes;
  }

}
