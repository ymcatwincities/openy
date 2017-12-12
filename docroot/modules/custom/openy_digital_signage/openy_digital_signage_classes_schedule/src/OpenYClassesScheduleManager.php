<?php

namespace Drupal\openy_digital_signage_classes_schedule;

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
  public function getClassesSchedule($period, $room_id) {
    $datetime = new \DateTime();
    $datetime->setTimezone(new \DateTimeZone('UTC'));
    $datetime->setTimestamp($period['to']);
    $period_to = $datetime->format('c');
    $datetime->setTimestamp($period['from']);
    $period_from = $datetime->format('c');

    $eq = $this->storage->getQuery();
    $eq->condition('room', $room_id)
      ->condition('date_time.value', $period_to, '<=')
      ->condition('date_time.end_value', $period_from, '>=')
      ->condition('overridden', FALSE)
      ->sort('date_time.value');
    if (!$results = $eq->execute()) {
      $eq = $this->storage->getQuery();
      $eq->condition('room', $room_id)
        ->condition('date_time.value', $period_from, '>=')
        ->condition('overridden', FALSE)
        ->sort('date_time.value')
        ->range(0, 1);
      $results = $eq->execute();
    }

    $class_sessions = $this->storage->loadMultiple($results);

    $classes = [];
    foreach ($class_sessions as $class_session) {
      $from = strtotime($class_session->date_time->value . 'z');
      $to = strtotime($class_session->date_time->end_value . 'z');
      $classes[] = [
        'from' => $from,
        'to' => $to,
        'trainer' => $this->prepareTrainerName($class_session->instructor->value),
        'substitute_trainer' => $this->prepareTrainerName($class_session->sub_instructor->value),
        'name' => $this->prepareClassName($class_session->label()),
        'from_formatted' => date('g:ia', $from),
        'to_formatted' => date('g:ia', $to),
      ];
    }

    return $classes;
  }

  /**
   * Prepare class name to display.
   *
   * @param string $name
   *   Class name.
   *
   * @return string
   *   Prepared to display class name.
   */
  protected function prepareClassName($name) {
    $name = str_replace('®', '<sup>®</sup>', trim($name));
    $name = str_replace('™', '<sup>™</sup>', $name);

    return $name;
  }

  /**
   * Truncate last name into short version.
   *
   * @param string $name
   *   Trainer name.
   *
   * @return string
   *   Return first name and only first letter of last name.
   */
  protected function prepareTrainerName($name) {
    $new_name = '';
    if (empty($name)) {
      return $new_name;
    }
    // Divide name into 2 parts.
    $array = explode(' ', trim($name));
    // Add first name to the new name.
    $new_name .= $array[0];
    if (empty($array[1])) {
      return $new_name;
    }
    // Verify is last name full or already cut to one symbol and point.
    if (strlen($array[1]) == 2 && substr($array[1], 1, 1) == '.') {
      // Leave as is.
      $new_name .= ' ' . $array[1];
    }
    else {
      // Add only first latter of last name..
      $new_name .= ' ' . strtoupper(substr($array[1], 0, 1)) . '.';
    }

    return $new_name;
  }

}
