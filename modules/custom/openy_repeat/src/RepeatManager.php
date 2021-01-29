<?php

namespace Drupal\openy_repeat;

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
use Drupal\openy_repeat_entity\Entity\Repeat;
use Drupal\openy_session_instance\SessionInstanceManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

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
   * OpenY Repeat config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

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
    $this->config = $this->configFactory->get('openy_repeat.settings');
    $this->moduleHandler = $module_handler;
    $this->moderationWrapper = $moderation_wrapper;
  }

  /**
   * {@inheritdoc}
   */
  public function resetCache() {
    $result = $this->entityTypeManager->getStorage('repeat')->getQuery()->execute();
    if (empty($result)) {
      return;
    }
    $this->deleteCacheItems($result);
    $this->logger->info('The cache was cleared.');
    $this->moduleHandler->invokeAll('openy_repeat_reset_cache');
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
    $ids = $this->entityTypeManager
      ->getStorage('repeat')
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
      try {
        if (!$activity->field_activity_category->isEmpty() && $program_subcategory = $activity->field_activity_category->referencedEntities()) {
          $program_subcategory = reset($program_subcategory);

          // Some instances may require unpublished references.
          // Do not skip program subcategory in that case.
          if (!$this->config->get('allow_unpublished_references')) {
            if (!$moderation_wrapper->entity_moderation_status($program_subcategory)) {
              // Skip activity due to unpublished program subcategory.
              continue;
            }
          }

          // Program reference.
          if ($program_subcategory->field_category_program && $program = $program_subcategory->field_category_program->referencedEntities()) {
            $program = reset($program);

            // Some instances may require unpublished references.
            // Do not skip program in that case.
            if (!$this->config->get('allow_unpublished_references')) {
              if (!$moderation_wrapper->entity_moderation_status($program)) {
                // Skip activity due to unpublished program.
                continue;
              }
            }

            $activity_ids[] = $activity->id();
            $program_subcategory_ids[] = $program_subcategory->id();
            $program_ids[] = $program->id();
          }
        }
      }
      catch (\Exception $e) {
        $this->logger->error($e->getMessage());
      }
    }

    if (empty($program_subcategory_ids) || empty($program_ids)) {
      return NULL;
    }

    // Instructor and Room text fields.
    $instructor = !$session->field_session_instructor->isEmpty() ? $session->field_session_instructor->value : '';
    $room = !$session->field_session_room->isEmpty() ? $session->field_session_room->value : '';

    $activity = reset($activities);

    $reg_link = $session->field_session_reg_link->getValue();
    if (!empty($reg_link[0]['uri'])) {
      $register_url = $reg_link[0]['uri'];
      $register_text = !empty($reg_link[0]['title']) ? $reg_link[0]['title'] : t('Register');
    }

    // All references are in the chain, return data.
    return [
      'title2' => $session->label(),
      'session' => $session->id(),
      'location' => $location_id,
      'facility' => !empty($facility) ? $facility->getTitle() : NULL,
      'category' => !empty($activities) ? $activity->getTitle() : NULL,
      'class' => $class_id,
      'field_si_activity' => array_unique($activity_ids),
      'field_si_program_subcategory' => array_unique($program_subcategory_ids),
      'field_si_program' => array_unique($program_ids),
      'min_age' => $session->field_session_min_age->value,
      'max_age' => $session->field_session_max_age->value,
      'instructor' => $instructor,
      'room' => $room,
      'register_url' => !empty($register_url) ? $register_url : NULL,
      'register_text' => !empty($register_text) ? $register_text : NULL,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function calcSessionInstancesBySchedule(array $session_schedule, $timestamp = NULL) {
    $session_instances = [];

    $weekday_mapping = [
      'monday' => '1',
      'tuesday' => '2',
      'wednesday' => '3',
      'thursday' => '4',
      'friday' => '5',
      'saturday' => '6',
      'sunday' => '7',
    ];

    foreach ($session_schedule['dates'] as $schedule_item) {
      foreach ($schedule_item['days'] as $weekDay) {
        // Starting period could start on Sunday. But we have Monday in the
        // settings. This is why we need to adjust day to proper day of
        // the week. Also similar thing with end date. We should move it back
        // till it reaches specified day of the week.
        $startDate = new \DateTime($schedule_item['period']['from']);
        while (strtolower($startDate->format('l')) !== $weekDay) {
          $startDate->modify('+1 day');
        }
        $endDate = new \DateTime($schedule_item['period']['to']);
        while (strtolower($endDate->format('l')) !== $weekDay) {
          $endDate->modify('-1 day');
        }

        $start = $startDate->format('Y-m-d') . 'T' . $schedule_item['time']['from'];
        $end = $endDate->format('Y-m-d') . 'T' . $schedule_item['time']['to'];
        $dates = [[
          'from' => $start,
          'to' => $end,
        ]];
        $exclusions = self::reorderExclusions($session_schedule['exclusions']);
        $combined_dates = self::combineDates($dates, $exclusions);

        foreach ($combined_dates as $date) {
          $to_time = strtotime(date('Y-m-d') .' '. $schedule_item['time']['to']);
          $from_time = strtotime(date('Y-m-d') .' '. $schedule_item['time']['from']);
          $duration = round(abs($to_time - $from_time) / 60,2);

          $day = $weekday_mapping[$weekDay];
          // Monthly events don't have exact week day, but the day of month.
          if ($schedule_item['recurring'] && $schedule_item['recurring'] == 'monthly') {
            $day = '*';
          }

          $session_instances[] = [
            'start' => strtotime($date['from']),
            'end' => strtotime($date['to']),
            'year' => '*',
            'month' => '*',
            'day' => '*',
            'week' => '*',
            'weekday' => $day,
            'duration'=> $duration,
          ];
        }
      }
    }

    return $session_instances;
  }

  /**
   * Return exclusions in order from earlier for later.
   *
   * @param array $exclusions
   *   Exclusions dates in session.
   *
   * @return array
   */
  public static function reorderExclusions($exclusions) {
    $new_exclusions = [];
    foreach ($exclusions as $exclusion) {
      $from = strtotime($exclusion['from']);
      $new_exclusions[$from] = $exclusion;
    }
    sort($new_exclusions);

    return $new_exclusions;
  }

  /**
   * Return combined dates.
   *
   * @param array $origin_dates
   *   Start and End of the Session.
   * @param array $exclusions
   *   Exclusions dates in session.
   *
   * @return array
   */
  public static function combineDates($origin_dates, $exclusions) {
    if (empty($exclusions) || !is_array($exclusions)) {
      return $origin_dates;
    }

    foreach ($exclusions as $key => $exclusion) {
      $exclusions[$key] = [
        'from' => new \DateTime($exclusion['from']),
        'to' => new \DateTime($exclusion['to']),
      ];
    }

    $start = new \DateTime($origin_dates[0]['from']);
    $end = new \DateTime($origin_dates[0]['to']);

    $resultingPeriods = [];
    $resultingPeriods[] = ['from' => clone $start];

    $prevSkipInstance = FALSE;

    while ($start < $end) {

      // Check if this instance should be excluded.
      $skipInstance = FALSE;
      foreach ($exclusions as $exclusion) {
        if ($start >= $exclusion['from'] && $start <= $exclusion['to']) {
          $skipInstance = TRUE;
          break;
        }
      }

      // We just hit exclusion. Need to close previous resulting period.
      if ($skipInstance && !$prevSkipInstance) {
        $period = array_pop($resultingPeriods);
        $close = clone $start;
        $close->modify('-1 week');
        $close->setTime($end->format('H'), $end->format('i'));
        $period['to'] = $close;
        array_push($resultingPeriods, $period);
      }

      // If exclusion just finished. We need to start new period.
      if (!$skipInstance && $prevSkipInstance) {
        $period = ['from' => clone $start];
        array_push($resultingPeriods, $period);
      }
      $start->modify('+1 week');
      $prevSkipInstance = $skipInstance;
    }

    // Do not forget to close last period.
    $period = array_pop($resultingPeriods);
    $reversExclusions = array_reverse($exclusions);
    foreach ($reversExclusions as $exclusion) {
      $endExclusion = $exclusion['to']->format('Y-m-d');
      $startExclusion = $exclusion['from']->format('Y-m-d');
      $endOrigin = $end->format('Y-m-d');

      if ($endExclusion == $endOrigin || $startExclusion == $endOrigin) {
        $end->modify('-1 week');
      }
    }
    $period['to'] = $end;
    array_push($resultingPeriods, $period);

    // Now convert dates back to strings.
    $result_dates = [];
    foreach ($resultingPeriods as $period) {
      // Example format 2018-01-08T05:15:00
      $result_dates[] = [
        'from' => $period['from']->format('Y-m-d\TH:i:s'),
        'to' => $period['to']->format('Y-m-d\TH:i:s'),
      ];
    }

    return $result_dates;
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
      if (empty($date) || empty($date->field_session_time_date->getValue())) {
        continue;
      }

      $schedule_item = [
        'days' => [],
        'period' => [],
        'time' => [],
      ];

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

      $schedule_item['recurring'] = FALSE;
      if ($date->hasField('field_session_recurring') && !$date->get('field_session_recurring')->isEmpty()) {
        $schedule_item['recurring'] = $date->get('field_session_recurring')->value;
      }

      foreach ($date->field_session_time_days->getValue() as $value) {
        $schedule_item['days'][] = $value['value'];
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
    if (!$this->config->get('allow_unpublished_references')) {
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

    $query = $this->entityTypeManager
      ->getStorage('repeat')
      ->getQuery()
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

    $query = $this->entityTypeManager
      ->getStorage('repeat')
      ->getQuery()
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

  /**
   * {@inheritdoc}
   */
  public function getAgeIds(NodeInterface $session) {
    $age_ids = [];
    static $terms = [];
    $s_min = !empty($session->field_session_min_age->value) ? $session->field_session_min_age->value : 0;
    $s_max = !empty($session->field_session_max_age->value) ? $session->field_session_max_age->value : 0;

    if (empty($terms)) {
      $query = $this->entityTypeManager
        ->getStorage('taxonomy_term')
        ->getQuery()
        ->condition('vid', 'age');
      $entity_ids = $query->execute();
      $terms = $this->entityTypeManager
        ->getStorage('taxonomy_term')
        ->loadMultiple($entity_ids);
    }

    if (!empty($terms)) {
      foreach ($terms as $id => $term) {
        $ag_min = !empty($term->field_min_age->value) ? $term->field_min_age->value : 0;
        $ag_max = !empty($term->field_max_age->value) ? $term->field_max_age->value : 0;
        if (($s_min <= $ag_max || !$s_min || !$ag_max) && ($s_max >= $ag_min || !$s_max || !$ag_min)) {
          $age_ids[] = $id;
        }
      }
    }
    return $age_ids;
  }

  /**
   * Check that class, activity or program subcategory node significantly changed.
   *
   * @param \Drupal\node\NodeInterface $entity
   *   Class node.
   *
   * @return boolean
   *
   */
  public function isSignificantChange(NodeInterface $entity) {
    // Fields that contain significant values for entity
    $map_compare = [
      'class' => [
        'field_class_activity'
      ],
      'activity' => [
        'title',
        'field_activity_category',
      ],
      'program_subcategory' => [
        'field_category_program'
      ],
    ];

    $bundle = $entity->bundle();

    if (!isset($map_compare[$bundle])) {
      return FALSE;
    }

    if (isset($entity->original)) {
      $original = $entity->original;
      foreach ($map_compare[$bundle] as $field) {
        $new = $entity->get($field)->getValue();
        $old = $original->get($field)->getValue();

        if (isset($old) && isset($new) && $new != $old) {
          return TRUE;
        }
      }
    }
    return FALSE;
  }
}
