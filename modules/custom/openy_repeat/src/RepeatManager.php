<?php

namespace Drupal\openy_repeat;

use Drupal;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\node\NodeInterface;
use Drupal\openy_repeat_entity\Entity\Repeat;
use Drupal\openy_session_instance\SessionInstanceManagerInterface;

/**
 * Created by PhpStorm.
 */
class RepeatManager implements SessionInstanceManagerInterface {

  /**
   * Logger channel definition.
   */
  const CHANNEL = 'openy_repeat';

  /**
   * Collection name.
   */
  const STORAGE = 'repeat';

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
  public function resetCache() {
    $result = $this->entityQuery->get('repeat')->execute();
    if (empty($result)) {
      return;
    }
    $this->deleteCacheItems($result);
    $this->logger->info('The cache was cleared.');
    Drupal::moduleHandler()->invokeAll('openy_repeat_reset_cache');
  }

  /**
   * Delete Session instances items by IDs.
   *
   * @param array $ids
   *   Session instance items IDs.
   */
  private function deleteCacheItems(array $ids) {
    $chunks = array_chunk($ids, 10);
    foreach ($chunks as $chunk) {
      $entities = Repeat::loadMultiple($chunk);
      $this->storage->delete($entities);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSessionInstancesBySession(NodeInterface $node) {
    $ids = $this->entityQuery
      ->get('repeat')
      ->condition('session', $node->id())
      ->execute();

    return $this->storage->loadMultiple($ids);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteSessionInstancesBySession(NodeInterface $node) {
    $session_instances = $this->getSessionInstancesBySession($node);
    $this->storage->delete($session_instances);

    return count($session_instances);
  }

  /**
   * {@inheritdoc}
   */
  public function getSessionData(NodeInterface $session) {
    $moderation_wrapper = Drupal::service('openy_moderation_wrapper.entity_moderation_status');

    // Skip session with empty location reference.
    if (empty($session->field_session_location->target_id)) {
      return NULL;
    }
    $location_id = $session->field_session_location->target_id;

    // Class reference.
    if (!$class = $session->field_session_class->referencedEntities()) {
      return NULL;
    }
    $class = reset($class);

    // Facility reference.
    $facility = $session->field_session_plocation->referencedEntities();
    $facility = reset($facility);

    if (!$moderation_wrapper->entity_moderation_status($class)) {
      // Class is unpublished.
      return NULL;
    }
    $class_id = $class->id();

    // Activity reference.
    $activity_ids = [];
    if (!$activities = $class->field_class_activity->referencedEntities()) {
      return NULL;
    }
    foreach ($activities as $activity) {
      if (!$moderation_wrapper->entity_moderation_status($activity)) {
        // Skip unpublished activities.
        continue;
      }
      // Program Subcategory reference.
      if (!$activity->field_activity_category->isEmpty() && $program_subcategory = $activity->field_activity_category->referencedEntities()) {
        $program_subcategory = reset($program_subcategory);
        if (!$moderation_wrapper->entity_moderation_status($program_subcategory)) {
          // Skip activity due to unpublished program subcategory.
          continue;
        }

        // Program reference.
        if ($program = $program_subcategory->field_category_program->referencedEntities()) {
          $program = reset($program);
          if (!$moderation_wrapper->entity_moderation_status($program)) {
            // Skip activity due to unpublished program.
            continue;
          }
          $activity_ids[] = $activity->id();
          $program_subcategory_ids[] = $program_subcategory->id();
          $program_ids[] = $program->id();
        }
      }
    }

    if (empty($program_subcategory_ids) || empty($program_ids)) {
      return NULL;
    }

    // Instructor and Room text fields.
    $instructor = !$session->field_session_instructor->isEmpty() ? $session->field_session_instructor->value : '';
    $room = !$session->field_session_room->isEmpty() ? $session->field_session_room->value : '';

    // All references are in the chain, return data.
    return [
      'title2' => $session->label(),
      'session' => $session->id(),
      'location' => $location_id,
      'facility' => !empty($facility) ? $facility->getTitle() : NULL,
      'category' => !empty($program_subcategory) ? $program_subcategory->getTitle() : NULL,
      'class' => $class_id,
      'field_si_activity' => array_unique($activity_ids),
      'field_si_program_subcategory' => array_unique($program_subcategory_ids),
      'field_si_program' => array_unique($program_ids),
      'min_age' => $session->field_session_min_age->value,
      'max_age' => $session->field_session_max_age->value,
      'instructor' => $instructor,
      'room' => $room,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function calcSessionInstancesBySchedule(array $session_schedule, $timestamp = NULL) {
    $session_instances = [];

    $weekday_mapping = [
      'sunday' => '1',
      'monday' => '2',
      'tuesday' => '3',
      'wednesday' => '4',
      'thursday' => '5',
      'friday' => '6',
      'saturday' => '7',
    ];

    foreach ($session_schedule['dates'] as $schedule_item) {
      // @todo: Make exclusions work.
      if ($schedule_item['frequency'] == 'weekly' || $schedule_item['frequency'] == 'daily') {
        foreach ($schedule_item['days'] as $day) {
          $to_time = strtotime(date('Y-m-d') .' '. $schedule_item['time']['to']);
          $from_time = strtotime(date('Y-m-d') .' '. $schedule_item['time']['from']);
          $duration = round(abs($to_time - $from_time) / 60,2);
          $session_instances[] = [
            'start' => strtotime($schedule_item['period']['from'] . 'T' . $schedule_item['time']['from']),
            'end' => strtotime($schedule_item['period']['to'] . 'T' . $schedule_item['time']['to']),
            'year' => '*',
            'month' => '*',
            'day' => '*',
            'week' => '*',
            'weekday' => $weekday_mapping[$day],
            'duration'=> $duration,
          ];
        }
      }
    }

    return $session_instances;
  }

  /**
   * Fetches sessions schedule.
   *
   * @param NodeInterface $node
   *   The session node.
   *
   * @return array|bool
   *   An array representing schedule.
   */
  public static function loadSessionSchedule(NodeInterface $node) {
    if (!$node) {
      return FALSE;
    }

    $schedule = [
      'nid' => $node->id(),
      'dates' => [],
      'exclusions' => [],
    ];
    $dates = $node->field_session_time->referencedEntities();
    foreach ($dates as $date) {
      if (empty($date) || empty($date->field_session_time_days->getValue()) || empty($date->field_session_time_date->getValue())) {
        continue;
      }
      $schedule_item = [
        'frequency' => 'weekly',
        'days' => [],
        'period' => [],
        'time' => [],
      ];

      foreach ($date->field_session_time_days->getValue() as $value) {
        $schedule_item['days'][] = $value['value'];
      }

      $_period = $date->field_session_time_date->getValue()[0];
      $_from = DrupalDateTime::createFromTimestamp(strtotime($_period['value'] . 'Z'));
      $_to = DrupalDateTime::createFromTimestamp(strtotime($_period['end_value'] . 'Z'));

      $schedule_item['period']['from'] = $_from->format('Y-m-d');
      $schedule_item['period']['to'] = $_to->format('Y-m-d');

      $schedule_item['time']['from'] = $_from->format('H:i:s');
      $schedule_item['time']['to'] = $_to->format('H:i:s');

      if (!isset($schedule['from']) || $schedule_item['period']['from'] < $schedule['from']) {
        $schedule['from'] = $schedule_item['period']['from'];
      }
      if (!isset($schedule['to']) || $schedule_item['period']['to'] > $schedule['to']) {
        $schedule['to'] = $schedule_item['period']['to'];
      }

      $schedule['dates'][] = $schedule_item;
    }

    $schedule['exclusions'] = $node->field_session_exclusions->getValue();
    foreach ($schedule['exclusions'] as &$exclusion) {
      $exclusion['from'] = DrupalDateTime::createFromTimestamp(strtotime($exclusion['value'] . 'Z'))->format('Y-m-d\TH:i:s');
      $exclusion['to'] = DrupalDateTime::createFromTimestamp(strtotime($exclusion['end_value'] . 'Z'))->format('Y-m-d\TH:i:s');
      unset($exclusion['value'], $exclusion['end_value']);
    }

    return $schedule;
  }

  /**
   * {@inheritdoc}
   */
  public static function calcSessionInstancesBySession(NodeInterface $node, $timestamp = NULL) {
    $schedule = self::loadSessionSchedule($node);
    return self::calcSessionInstancesBySchedule($schedule);
  }

  /**
   * {@inheritdoc}
   */
  public function recreateSessionInstances(NodeInterface $node) {
    $this->deleteSessionInstancesBySession($node);

    // It's not published.
    if (!$node->isPublished()) {
      return;
    }

    // The session isn't complete or the chain is broken.
    if (!$session_data = $this->getSessionData($node)) {
      return;
    }

    $instances = self::calcSessionInstancesBySession($node);
    foreach ($instances as $instance) {
      $session_instance = Repeat::create($session_data + $instance);
      $session_instance->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getClosestUpcomingSessionInstanceBySession(NodeInterface $node, $from = NULL, $to = NULL) {
    // @todo check if it works with new logic.
    $session_instance = NULL;

    $query = $this->entityQuery
      ->get('repeat')
      ->condition('session', $node->id())
      ->sort('start')
      ->range(0, 1);
    if ($from) {
      $query->condition('start', $from, '>');
    }
    if ($to) {
      $query->condition('end', $to, '<');
    }
    $id = $query->execute();

    if ($id) {
      $session_instance = Repeat::load(reset($id));
    }

    return $session_instance;
  }

  /**
   * Modifies entity query, by adding a condition.
   *
   * @param \Drupal\Core\Entity\Query\QueryInterface $query
   *   The entity query.
   * @param string $key
   *   The condition key.
   * @param mixed $value
   *   The condition value.
   */
  private static function addEntityQueryCondition(QueryInterface &$query, $key, $value) {
    $simple = array(
      'from' => ['start', '>='],
      'to' => ['end', '<'],
    );

    if (isset($simple[$key])) {
      // @todo: Make date conditions work with new logic.
      list($field, $op) = $simple[$key];
      //$query->condition($field, $value, $op);
      return;
    }

    $query->condition($key, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function getSessionInstancesByParams(array $conditions) {
    $session_instances = [];

    $query = $this->entityQuery
      ->get('repeat')
      ->sort('start');

    foreach ($conditions as $key => $value) {
      self::addEntityQueryCondition($query, $key, $value);
    }

    $ids = $query->execute();

    if ($ids) {
      $session_instances = Repeat::loadMultiple($ids);
    }

    return $session_instances;
  }

  /**
   * {@inheritdoc}
   */
  public function getSessionsByParams(array $conditions) {
    $session_ids = [];

    // Sessions should match the programs.
    if (isset($conditions['program'])) {
      $query = Drupal::entityQuery('node')
        ->condition('type', 'program_subcategory')
        ->condition('field_category_program', $conditions['program'], 'IN');
      $program_subcategory_ids = $query->execute();

      if (!$program_subcategory_ids) {
        return [];
      }
    }
    // Sessions should match the program subcategories.
    if (!empty($conditions['program_subcategory']) || !empty($program_subcategory_ids)) {
      $query = Drupal::entityQuery('node')->condition('type', 'activity');
      if (!empty($conditions['program_subcategory'])) {
        $query->condition('field_activity_category', $conditions['program_subcategory'], 'IN');
      }
      if (!empty($program_subcategory_ids)) {
        $query->condition('field_activity_category', $program_subcategory_ids, 'IN');
      }
      $activity_ids = $query->execute();

      if (!$activity_ids) {
        return [];
      }
    }

    // Sessions should match the activities.
    if (!empty($conditions['activity']) || !empty($activity_ids)) {
      $query = Drupal::entityQuery('node')->condition('type', 'class');
      if (!empty($conditions['activity'])) {
        $query->condition('field_class_activity', $conditions['activity'], 'IN');
      }
      if (!empty($activity_ids)) {
        $query->condition('field_class_activity', $activity_ids, 'IN');
      }
      $class_ids = $query->execute();

      if (!$class_ids) {
        return [];
      }
    }

    // Sessions should match the classes.
    if (!empty($conditions['class']) || !empty($class_ids)) {
      $query = Drupal::entityQuery('node')->condition('type', 'session');
      if (!empty($conditions['class'])) {
        $query->condition('field_session_class', $conditions['class'], 'IN');
      }
      if (!empty($class_ids)) {
        $query->condition('field_session_class', $class_ids, 'IN');
      }
      $session_ids = $query->execute();
    }

    return $session_ids;
  }

  /**
   * {@inheritdoc}
   */
  public function getSessionInstancesByClassNode(NodeInterface $node, $conditions = []) {
    if ($node->bundle() != 'class') {
      return [];
    }
    // Current node is a class.
    $class_id = $node->id();

    // Current date as timestamp.
    $current_date = strtotime("today UTC");

    /* @see \Drupal\openy_schedules\Form\SchedulesSearchForm::getSessions */
    $conditions['class'] = $class_id;
    $conditions['from'] = $current_date;

    // Fetch session instances.
    $session_instances = $this->getSessionInstancesByParams($conditions);

    return $session_instances;
  }

  /**
   * Get nid array of locations for a given class node.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Class node.
   *
   * @return array
   *   Nids of locations.
   */
  private function getLocationIDsByClassNode(NodeInterface $node) {
    if ($node->bundle() != 'class') {
      return [];
    }
    // Current node is a class.
    $class_id = $node->id();

    // Current date as timestamp.
    $current_date = strtotime("today UTC");

    /* @see \Drupal\openy_schedules\Form\SchedulesSearchForm::getSessions */
    $conditions['class'] = $class_id;
    $conditions['from'] = $current_date;

    $upcoming_session_instances = $this->getSessionInstancesByParams($conditions);

    $nids = [];
    foreach ($upcoming_session_instances as $session_instance) {
      $location_id = $session_instance->location->target_id;
      $nids[$location_id] = $location_id;
    }

    return $nids;
  }

  /**
   * {@inheritdoc}
   */
  public function getLocationsByClassNode(NodeInterface $node) {
    $locations = [];
    $nids = $this->getLocationIDsByClassNode($node);

    if ($nids) {
      $locations = $this->entityTypeManager
        ->getStorage('node')
        ->loadMultiple($nids);
    }

    return $locations;
  }

  /**
   * {@inheritdoc}
   */
  public function getLocationCountByClassNode(NodeInterface $node) {
    $nids = $this->getLocationIDsByClassNode($node);

    return count($nids);
  }

}
