<?php

namespace Drupal\openy_session_instance;

use Drupal;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\node\NodeInterface;
use Drupal\openy_moderation_wrapper\EntityModerationStatus;
use Drupal\openy_session_instance\Entity\SessionInstance;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Created by PhpStorm.
 */
class SessionInstanceManager implements SessionInstanceManagerInterface {

  /**
   * Logger channel definition.
   */
  const CHANNEL = 'openy_session_instance';

  /**
   * Collection name.
   */
  const STORAGE = 'session_instance';

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
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Drupal\openy_moderation_wrapper\EntityModerationStatus definition.
   *
   * @var \Drupal\openy_moderation_wrapper\EntityModerationStatus
   */
  protected $moderationWrapper;

  /**
   * Constructor.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LoggerChannelFactoryInterface $logger_factory, ConfigFactoryInterface $configFactory, ModuleHandlerInterface $module_handler, EntityModerationStatus $moderation_wrapper) {
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger_factory->get(self::CHANNEL);
    $this->storage = $this->entityTypeManager->getStorage(self::STORAGE);
    $this->configFactory = $configFactory;
    $this->moduleHandler = $module_handler;
    $this->moderationWrapper = $moderation_wrapper;
  }

  /**
   * {@inheritdoc}
   */
  public function resetCache() {
    $result = $this->entityTypeManager
      ->getStorage('session_instance')
      ->getQuery()
      ->execute();
    if (empty($result)) {
      return;
    }
    $this->deleteCacheItems($result);
    $this->logger->info('The cache was cleared.');
    $this->moduleHandler->invokeAll('openy_session_instance_reset_cache');
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
      $entities = SessionInstance::loadMultiple($chunk);
      $this->storage->delete($entities);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSessionInstancesBySession(NodeInterface $node) {
    $ids = $this->entityTypeManager
      ->getStorage('session_instance')
      ->getQuery()
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
    $moderation_wrapper = $this->moderationWrapper;

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
      if ($program_subcategory = $activity->field_activity_category->referencedEntities()) {
        $program_subcategory = reset($program_subcategory);
        if (!$moderation_wrapper->entity_moderation_status($program_subcategory)) {
          // Skip activity due to unpublished program subcategory.
          continue;
        }

        // Program reference.
        if ($program_subcategory->field_category_program && $program = $program_subcategory->field_category_program->referencedEntities()) {
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

    // All references are in the chain, return data.
    return [
      'title' => $session->label(),
      'session' => $session->id(),
      'location' => $location_id,
      'class' => $class_id,
      'field_si_activity' => array_unique($activity_ids),
      'field_si_program_subcategory' => array_unique($program_subcategory_ids),
      'field_si_program' => array_unique($program_ids),
      'min_age' => $session->field_session_min_age->value,
      'max_age' => $session->field_session_max_age->value,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function calcSessionInstancesBySchedule(array $session_schedule, $timestamp = NULL) {
    $session_instances = [];

    if (!$timestamp) {
      // Make sure it calculates today's session instances.
      $timestamp = time() - 86400;
    }

    foreach ($session_schedule['dates'] as $schedule_item) {
      // Skip expired schedule items.
      $end_timestamp = strtotime($schedule_item['period']['to'] . ' +1day');
      if ($end_timestamp < $timestamp) {
        continue;
      }

      // Find closest occurrence according to the repeating rule.
      if ($schedule_item['frequency'] == 'weekly' || $schedule_item['frequency'] == 'daily') {
        $candidate_day = strtotime($schedule_item['period']['from'] . 'T' . $schedule_item['time']['from']);
        while ($candidate_day <= $end_timestamp) {
          if ($schedule_item['frequency'] == 'weekly' && !in_array(strtolower(date('l', $candidate_day)), $schedule_item['days'])) {
            // The day not into the schedule.
          }
          elseif ($candidate_day < $timestamp) {
            // The day is in the past.
          }
          else {
            $candidate_time_from = strtotime(date('Y-m-d', $candidate_day) . 'T' . $schedule_item['time']['from']);
            $candidate_time_to = strtotime(date('Y-m-d', $candidate_day) . 'T' . $schedule_item['time']['to']);

            // Check against exclusions.
            $excluded = FALSE;
            foreach ($session_schedule['exclusions'] as $exclusion) {
              if (
                $candidate_time_to >= strtotime($exclusion['from']) &&
                $candidate_time_from <= strtotime($exclusion['to'])
              ) {
                // Exclusion found.
                $excluded = TRUE;
              }
            }

            if (!$excluded) {
              $session_instances[] = [
                'from' => $candidate_time_from,
                'to' => $candidate_time_to,
              ];
            }
          }
          $candidate_day = strtotime('Next day', $candidate_day);
        }
      }
      else {
        // It's assumed, that schedule item date range is a single day and the
        // the exclusions doesn't affect it if the frequency is not set.
        $session_instances[] = [
          'from' => strtotime($schedule_item['period']['from'] . 'T' . $session_schedule['time']['from']),
          'to' => strtotime($schedule_item['period']['from'] . 'T' . $session_schedule['time']['to']),
        ];
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
    // Some OpenY instances may require unpublished references.
    if (!$this->configFactory->get('openy_session_instance')->get('allow_unpublished_references')) {
      if (!$node->isPublished()) {
        return;
      }
    }

    // The session isn't complete or the chain is broken.
    if (!$session_data = $this->getSessionData($node)) {
      return;
    }

    $instances = self::calcSessionInstancesBySession($node);
    foreach ($instances as $instance) {
      $session_instance = SessionInstance::create($session_data + [
        'timestamp' => $instance['from'],
        'timestamp_to' => $instance['to'],
      ]);
      $session_instance->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getClosestUpcomingSessionInstanceBySession(NodeInterface $node, $from = NULL, $to = NULL) {
    $session_instance = NULL;

    $query = $this->entityTypeManager
      ->getStorage('session_instance')
      ->getQuery()
      ->condition('session', $node->id())
      ->sort('timestamp')
      ->range(0, 1);
    if ($from) {
      $query->condition('timestamp', $from, '>');
    }
    if ($to) {
      $query->condition('timestamp', $to, '<');
    }
    $id = $query->execute();

    if ($id) {
      $session_instance = SessionInstance::load(reset($id));
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
      'from' => ['timestamp', '>='],
      'to' => ['timestamp', '<'],
    );

    if (isset($simple[$key])) {
      list($field, $op) = $simple[$key];
      $query->condition($field, $value, $op);
      return;
    }

    $query->condition($key, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function getSessionInstancesByParams(array $conditions) {
    $session_instances = [];

    $query = $this->entityTypeManager
      ->getStorage('session_instance')
      ->getQuery()
      ->sort('timestamp');

    foreach ($conditions as $key => $value) {
      self::addEntityQueryCondition($query, $key, $value);
    }

    $ids = $query->execute();

    if ($ids) {
      $session_instances = SessionInstance::loadMultiple($ids);
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
      $query = $this->entityTypeManager
        ->getStorage('node')
        ->getQuery()
        ->condition('type', 'program_subcategory')
        ->condition('field_category_program', $conditions['program'], 'IN');
      $program_subcategory_ids = $query->execute();

      if (!$program_subcategory_ids) {
        return [];
      }
    }
    // Sessions should match the program subcategories.
    if (!empty($conditions['program_subcategory']) || !empty($program_subcategory_ids)) {
      $query = $this->entityTypeManager
        ->getStorage('node')
        ->getQuery()
        ->condition('type', 'activity');
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
      $query = $this->entityTypeManager
        ->getStorage('node')
        ->getQuery()
        ->condition('type', 'class');
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
      $query = $this->entityTypeManager
        ->getStorage('node')
        ->getQuery()
        ->condition('type', 'session');
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
