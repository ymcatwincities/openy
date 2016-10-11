<?php

namespace Drupal\ymca_google;

use Drupal\Component\Utility\SortArray;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\ymca_groupex_google_cache\Entity\GroupexGoogleCache;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\ymca_groupex\GroupexRequestTrait;
use Drupal\ymca_groupex_google_cache\GroupexGoogleCacheInterface;

/**
 * Class DrupalProxy.
 *
 * @package Drupal\ymca_groupex
 */
class DrupalProxy implements DrupalProxyInterface {

  /**
   * Max allowed updates per run.
   */
  const PROXY_UPDATE_PER_RUN = 100;

  /**
   * Max children for single parent entity.
   */
  const MAX_CHILD_WARNING = 100;

  /**
   * Entity load chunk.
   */
  const ENTITY_LOAD_CHUNK = 100;

  /**
   * Data wrapper.
   *
   * @var GcalGroupexWrapper
   */
  protected $dataWrapper;

  /**
   * Timezone object.
   *
   * @var \DateTimeZone
   */
  protected $timezone;

  /**
   * Query factory.
   *
   * @var QueryFactory
   */
  protected $queryFactory;

  /**
   * Logger.
   *
   * @var LoggerChannelInterface
   */
  protected $logger;

  /**
   * Data fetcher.
   *
   * @var GroupexDataFetcher
   */
  protected $fetcher;

  /**
   * The plugin manager.
   *
   * @var GCalUpdaterManager
   */
  protected $pluginManager;

  /**
   * Entity type manager.
   *
   * @var EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The cache storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $cacheStorage;

  /**
   * DrupalProxy constructor.
   *
   * @param GcalGroupexWrapper $data_wrapper
   *   Data wrapper.
   * @param QueryFactory $query_factory
   *   Query factory.
   * @param LoggerChannelInterface $logger
   *   Logger factory.
   * @param GroupexDataFetcher $fetcher
   *   Groupex data fetcher.
   * @param GCalUpdaterManager $plugin_manager
   *   The manager for updater plugins.
   * @param EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   */
  public function __construct(GcalGroupexWrapper $data_wrapper, QueryFactory $query_factory, LoggerChannelInterface $logger, GroupexDataFetcher $fetcher, GCalUpdaterManager $plugin_manager, EntityTypeManagerInterface $entity_type_manager) {
    $this->dataWrapper = $data_wrapper;
    $this->queryFactory = $query_factory;
    $this->logger = $logger;
    $this->fetcher = $fetcher;
    $this->pluginManager = $plugin_manager;
    $this->entityTypeManager = $entity_type_manager;

    $this->timezone = new \DateTimeZone(GcalGroupexWrapper::TIMEZONE);
    $this->cacheStorage = $this->entityTypeManager->getStorage(GcalGroupexWrapper::ENTITY_TYPE);
  }

  /**
   * {@inheritdoc}
   */
  public function saveEntities() {
    $this->processIcsData();
    $this->processSchedulesData();
  }

  /**
   * Process schedules data.
   */
  protected function processSchedulesData() {
    $api_version = $this->dataWrapper->settings->get('api_version');
    switch ($api_version) {
      case 1:
        $this->processSchedulesDataLegacy();
        break;

      case 2:
        $this->processSchedulesDataCurrent();
        break;

    }
  }

  /**
   * Process schedules data.
   *
   * Currently is working on production.
   */
  protected function processSchedulesDataLegacy() {
    $frame = $this->dataWrapper->getTimeFrame();
    $entities = [
      'insert' => [],
      'update' => [],
      'delete' => [],
    ];

    foreach ($this->dataWrapper->getSourceData() as $item) {
      // Generate timestamps.
      $timestamps = $this->buildTimestamps($item->date, $item->time);
      $item->timestamp_start = $timestamps['start'];
      $item->timestamp_end = $timestamps['end'];

      // Try to find existing cache item.
      $existing = $this->findParentEntityByClassId($item->id);

      // Create entity, if ID doesn't exist.
      if (!$existing) {
        $cache_item = GroupexGoogleCache::create([
          'field_gg_category' => $item->category,
          'field_gg_class_id' => $item->id,
          'field_gg_date' => [$item->date],
          'field_gg_description' => $item->desc,
          'field_gg_instructor' => $item->instructor,
          'field_gg_location' => $item->location,
          'field_gg_orig_instructor' => $item->original_instructor,
          'field_gg_studio' => $item->studio,
          'field_gg_sub_instructor' => $item->sub_instructor,
          'field_gg_time' => $item->time,
          'field_gg_title' => $item->title,
          'field_gg_timestamp_end' => $item->timestamp_end,
          'field_gg_timestamp_start' => $item->timestamp_start,
          'field_gg_time_frame_start' => $frame['start'],
          'field_gg_time_frame_end' => $frame['end'],
        ]);
        $cache_item->setName($item->title . ' [' . $item->id . ']');
        $cache_item->save();
        $entities['insert'][] = $cache_item;
      }
      else {
        if ($existing->get('field_gg_gcal_id')->isEmpty()) {
          $entities['insert'][] = $existing;
        }
        else {
          // Proceed only with changed entities.
          $diff = $this->diff($existing, $item);
          if (!empty($diff['date']) || !empty($diff['fields']) || !empty($diff['needs_update'])) {
            // Update fields if updates exist.
            foreach ($diff['fields'] as $field_name => $value) {
              $existing->set($field_name, $value);
            }

            // The event is recurring. Append new date and extend time frame.
            if (!empty($diff['date'])) {
              $field_date = $existing->get('field_gg_date');
              $field_date->appendItem($diff['date']);
              $existing->set('field_gg_time_frame_end', $frame['end']);
            }

            // Add entity to update list.
            $entities['update'][] = $existing;
          }
        }
      }
    }

    // Check whether entities were deleted from groupex.
    $cached_ids = $this->findByTimeFrame($frame['start'], $frame['end']);
    $fetched_ids = [];

    // Get IDs of fetched classes.
    foreach ($this->dataWrapper->getSourceData() as $item) {
      $fetched_ids[$item->id] = $item->id;
    }

    $delete_ids = array_diff($cached_ids, $fetched_ids);
    foreach ($delete_ids as $delete_id) {
      // Make sure we deleting really deleted event.
      $result = $this->fetcher->getClassById($delete_id);
      if ($result && $result->description == 'No description available.') {
        $entities['delete'][] = $this->findParentEntityByClassId($delete_id);
      }
    }

    $this->dataWrapper->setProxyData($entities);
  }

  /**
   * Process schedules data.
   *
   * Using child items.
   */
  protected function processSchedulesDataCurrent() {
    // Get compare map. Do not compare class date field.
    $field_map_schedules = $this->dataWrapper->getFieldMappingSchedules();
    $compare_map = $field_map_schedules;
    unset($compare_map['field_gg_date_str']);

    foreach ($this->dataWrapper->getSourceData() as $class) {
      // Skip entities which we have been already processed.
      if ($this->isFullHashExists($this->getClassFullHash($class))) {
        continue;
      }

      // Get base entity ID.
      $parent = $this->findParentEntityByClassId($class->id);
      if (!$parent) {
        // Parent entity may not exists as entity yet.
        // It may be created in next iterations. So, just logging and skipping.
        $this->logger->notice(
          'Parent entity for class ID %id was not found.',
          [
            '%id' => $class->id,
          ]
        );
        continue;
      }

      $children = $this->findChildren($parent->id());
      if (!$children) {
        // No children found. Create one.
        $this->createChildCacheItem($class);

        // Update base (parent) entity with child data.
        foreach ($compare_map as $field => $property) {
          $parent->set($field, $class->$property);
        }
        $parent->save();

      }
      else {
        if (count($children) > self::MAX_CHILD_WARNING) {
          $msg = 'Got more than %count of children for parent entity with ID %id.';
          $this->logger->warning(
            $msg,
            [
              '%count' => self::MAX_CHILD_WARNING,
              '%id' => $parent->id(),
            ]
          );
        }

        if ($this->isDifferent($compare_map, $parent, $class)) {
          // The class differs from base entity. Create new child.
          $this->createChildCacheItem($class);
        }
      }
    }

    // Find all child entities without GCal ID.
    // Their parents should be passed to update.
    $updated = $this->findUpdatedScheduleItems();
    foreach ($updated as $entity) {
      $this->dataWrapper->appendProxyItem('update', $entity);
    }
  }

  /**
   * Process ICS data.
   */
  protected function processIcsData() {
    $field_map = $this->dataWrapper->getFieldMappingIcs();

    $updated_items = 0;
    $max_updated_items = $this->dataWrapper->settings->get('proxy_update_per_run') ?: self::PROXY_UPDATE_PER_RUN;

    $updated_items_ids = [];
    $created_items_ids = [];

    foreach ($this->dataWrapper->getIcsData() as $item) {
      // Do not process huge amount of data.
      if ($updated_items >= $max_updated_items) {
        $this->logger->info('Proxy. Max number of updated items reached.');
        break;
      }

      // Try to find existing item.
      $existing = $this->findParentEntityByClassId($item->id);
      if (!$existing) {
        $updated_items++;

        // Create new entity.
        $storage = $this->entityTypeManager->getStorage(GcalGroupexWrapper::ENTITY_TYPE);
        $values = [];
        foreach ($field_map as $field_name => $property) {
          $values[$field_name] = $item->$property;
        }
        $entity = $storage->create($values);
        $entity->setName($item->title . ' [' . $item->id . ']');
        $entity->set('field_gg_class_id', $item->id);
        $entity->save();

        $created_items_ids[] = $entity->id();
      }
      else {
        // Update existing entity if it differs.
        if (FALSE === $this->isDifferent($field_map, $existing, $item)) {
          continue;
        }

        foreach ($field_map as $field_name => $property) {
          if (!empty($property)) {
            $existing->set($field_name, $item->$property);
          }
        }

        $existing->set('field_gg_need_up', TRUE);

        $existing->save();
        $updated_items_ids[] = $existing->id();
        $updated_items++;
      }
    }

    $msg = 'Proxy. Updated items: %updated, created: %created.';
    $this->logger->info(
      $msg,
      [
        '%updated' => implode(', ', $updated_items_ids),
        '%created' => implode(', ', $created_items_ids),
      ]
    );

    $api_version = $this->dataWrapper->settings->get('api_version');

    // ICS introduced only in API 2.
    // Pass ICS data to pusher if API >= 2.
    if ($api_version >= 2) {
      // Process created items.
      $created = $this->findCreatedIcsItems();
      foreach ($created as $entity) {
        $this->dataWrapper->appendProxyItem('insert', $entity);
      }

      // Process updated items.
      $updated = $this->findUpdatedIcsItems();
      foreach ($updated as $entity) {
        $this->dataWrapper->appendProxyItem('update', $entity);
      }

      // Process deleted items.
      $deleted = $this->findDeletedIcsItems();
      $delete_count = count($deleted);
      if ($delete_count < 100) {
        foreach ($deleted as $entity) {
          $this->dataWrapper->appendProxyItem('delete', $entity);
        }
      }
      else {
        $msg = 'Too many items %count for deleting. Needs checking.';
        $this->logger->critical(
          $msg,
          [
            '%count' => $delete_count,
          ]
        );
      }
    }
  }

  /**
   * Find parents of not pushed children.
   *
   * @return array
   *   List of parent entities.
   */
  protected function findUpdatedScheduleItems() {
    $updated = [];

    $result = $this->queryFactory->get('groupex_google_cache')
      ->exists('field_gg_parent_ref')
      ->notExists('field_gg_gcal_id')
      ->execute();

    if (empty($result)) {
      return [];
    }

    $chunks = array_chunk($result, self::ENTITY_LOAD_CHUNK);
    foreach ($chunks as $chunk) {
      $entities = $this->cacheStorage->loadMultiple($chunk);
      foreach ($entities as $entity) {
        $parent_id = $entity->field_gg_parent_ref->target_id;
        if (!array_key_exists($parent_id, $updated)) {
          $updated[$parent_id] = $this->cacheStorage->load($parent_id);
        }
      }
    }

    return $updated;
  }

  /**
   * Find items to be updated.
   *
   * @return array
   *   List of items to be updated.
   */
  protected function findUpdatedIcsItems() {
    $updated = [];

    $result = $this->queryFactory->get('groupex_google_cache')
      ->condition('field_gg_need_up', TRUE)
      ->notExists('field_gg_parent_ref')
      ->execute();

    if (empty($result)) {
      return [];
    }

    $chunks = array_chunk($result, self::ENTITY_LOAD_CHUNK);
    foreach ($chunks as $chunk) {
      $entities = $this->cacheStorage->loadMultiple($chunk);
      foreach ($entities as $entity) {
        $updated[] = $entity;
      }
    }

    return $updated;
  }

  /**
   * Find created ICS items.
   *
   * @return array
   *   List of created entities.
   */
  protected function findCreatedIcsItems() {
    $created = [];

    $result = $this->queryFactory->get('groupex_google_cache')
      ->notExists('field_gg_parent_ref')
      ->notExists('field_gg_gcal_id')
      ->execute();

    if (empty($result)) {
      return [];
    }

    $chunks = array_chunk($result, self::ENTITY_LOAD_CHUNK);
    foreach ($chunks as $chunk) {
      $entities = $this->cacheStorage->loadMultiple($chunk);
      foreach ($entities as $entity) {
        $created[] = $entity;
      }
    }

    return $created;
  }

  /**
   * Find items deleted from ICS.
   *
   * @return array
   *   The list of parent entities which are not exist in ICS.
   */
  protected function findDeletedIcsItems() {
    $deleted = [];

    $ics_data = $this->dataWrapper->getIcsData();

    // Do not process empty data. That means something with ICS API.
    if (empty($ics_data)) {
      return [];
    }

    $result = $this->queryFactory->get('groupex_google_cache')
      ->notExists('field_gg_parent_ref')
      ->execute();

    if (empty($result)) {
      return [];
    }

    // Check if each local parent entity exists in the ICS data.
    $chunks = array_chunk($result, self::ENTITY_LOAD_CHUNK);
    foreach ($chunks as $chunk) {
      $entities = $this->cacheStorage->loadMultiple($chunk);
      foreach ($entities as $entity) {
        $found = FALSE;
        foreach ($ics_data as $ics_item) {
          if ($ics_item->id == $entity->field_gg_class_id->value) {
            $found = TRUE;
            break;
          }
        }

        if (FALSE === $found) {
          $deleted[] = $entity;
        }
      }
    }

    // Check API to be sure that class is really deleted.
    $deleted_for_sure = [];
    foreach ($deleted as $i => $entity) {
      $result = $this->fetcher->getClassById($entity->field_gg_class_id->value);
      if ($result && $result->description == 'No description available.') {
        $deleted_for_sure[] = $entity;
      }
    }

    return $deleted_for_sure;
  }

  /**
   * Increase weight of a cache entity.
   *
   * @param \Drupal\ymca_groupex_google_cache\GroupexGoogleCacheInterface $entity
   *   Entity.
   */
  protected function updateWeight(GroupexGoogleCacheInterface $entity) {
    $field = 'field_gg_weight';
    $current = (int) $entity->$field->value;
    $entity->set($field, $current + 1);
    $entity->save();
  }

  /**
   * Creates child cache entity.
   *
   * @param \stdClass $class
   *   Groupex class object.
   *
   * @return int|bool
   *   Cache entity ID.
   */
  protected function createChildCacheItem(\stdClass $class) {
    $field_map = $this->dataWrapper->getFieldMappingSchedules();
    $storage = $this->entityTypeManager->getStorage(GcalGroupexWrapper::ENTITY_TYPE);

    if (!$parent = $this->findParentEntityByClassId($class->id)) {
      $msg = 'Parent entity was not found for class ID %id';
      $this->logger->error(
        $msg,
        [
          '%id' => $class->id,
        ]
      );
      return FALSE;
    }

    $values = [
      'field_gg_hash_full' => $this->getClassFullHash($class),
      'field_gg_parent_ref' => $parent->id(),
      'field_gg_weight' => 1,
    ];

    foreach ($field_map as $field_name => $property) {
      $values[$field_name] = $class->$property;
    }

    try {
      $entity = $storage->create($values);
      $entity->setName($class->title . ' [' . $class->id . ']');
      $entity->save();

      $msg = 'New child item for parent entity %id was created.';
      $this->logger->info(
        $msg,
        [
          '%id' => $entity->id(),
        ]
      );

      return $entity->id();
    }
    catch (\Exception $e) {
      $msg = 'Failed to create child cache item for class with ID %id. Error: %error';
      $this->logger->error(
        $msg,
        [
          '%id' => $class->id,
          '%error' => $e->getMessage(),
        ]
      );
      return FALSE;
    }
  }

  /**
   * Get class full hash.
   *
   * @param \stdClass $class
   *   Class object.
   *
   * @return string
   *   Hash.
   */
  protected function getClassFullHash(\stdClass $class) {
    return md5(serialize($class));
  }

  /**
   * Check whether full class hash exists.
   *
   * @param string $hash
   *   Hash string.
   *
   * @return bool
   *   True if the hash exists.
   */
  protected function isFullHashExists($hash) {
    $result = $this->queryFactory->get('groupex_google_cache')
      ->condition('field_gg_hash_full', $hash)
      ->execute();
    if (!empty($result)) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Get all available types of recurrence.
   *
   * @return array
   *   Types of recurrence.
   */
  public function getRecurrenceTypes() {
    $types = [];

    $result = $this->queryFactory->get('groupex_google_cache')
      ->notExists('field_gg_parent_ref')
      ->execute();

    if (empty($result)) {
      return [];
    }

    $chunks = array_chunk($result, self::ENTITY_LOAD_CHUNK);
    foreach ($chunks as $chunk) {
      foreach ($this->cacheStorage->loadMultiple($chunk) as $entity) {
        $value = $entity->field_gg_ics_rec->value ?: 'NONE';
        $types[] = $value;
      }
    }

    return array_count_values($types);
  }

  /**
   * Check whether saved entity differs from class object (by fields).
   *
   * @param array $map
   *   The map of field names and properties to compare.
   * @param GroupexGoogleCacheInterface $entity
   *   Entity.
   * @param \stdClass $class
   *   Groupex class.
   *
   * @return bool
   *   True if if the entity differs from class.
   */
  protected function isDifferent(array $map, GroupexGoogleCacheInterface $entity, \stdClass $class) {
    foreach ($map as $field_name => $property) {
      $entity_value = $entity->{$field_name}->value;
      $groupex_value = $class->{$property};
      if (strcmp($entity_value, $groupex_value) !== 0) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Diffs entity saved in DB and groupex class item.
   *
   * @param GroupexGoogleCache $entity
   *   Entity.
   * @param \stdClass $class
   *   Class item.
   *
   * @return mixed
   *   Diff array.
   */
  protected function diff(GroupexGoogleCache $entity, \stdClass $class) {
    /* The are two features we should compare:
    1. The fields. Some fields may be updated. For, example "title".
    2. A new date for recurring entity may be added. */

    $diff['fields'] = [];
    $diff['date'] = [];

    // Simply compare field values (without date field and ID).
    $compare = [
      'field_gg_category' => 'category',
      'field_gg_description' => 'desc',
      'field_gg_instructor' => 'instructor',
      'field_gg_location' => 'location',
      'field_gg_orig_instructor' => 'original_instructor',
      'field_gg_studio' => 'studio',
      'field_gg_sub_instructor' => 'sub_instructor',
      'field_gg_title' => 'title',
      'field_gg_time' => 'time',
    ];

    foreach ($compare as $drupal_field => $groupex_field) {
      $drupal_value = $entity->{$drupal_field}->value;
      $groupex_value = $class->{$groupex_field};
      if (strcmp($drupal_value, $groupex_value) !== 0) {
        $diff['fields'][$drupal_field] = $groupex_value;
      }
    }

    /* Field 'field_gg_date' is multiple, so, we need to compare each value
    with the new date. If we don't find it in the least we'll get new
    recurring date. */

    $found = FALSE;
    $field_date = $entity->get('field_gg_date');
    $list = $field_date->getValue();
    foreach ($list as $list_item) {
      if (strcmp($list_item['value'], $class->date) == 0) {
        $found = TRUE;
      }
    }

    // The event is recurring and the date is new. Add it to the diff result.
    if (!$found) {
      $diff['date'] = $class->date;
    }

    // Loop over updaters to check whether entity needs to be updated.
    $diff['needs_update'] = FALSE;
    $definitions = $this->pluginManager->getDefinitions();
    foreach ($definitions as $definition) {
      /** @var \Drupal\ymca_google\GCalUpdaterInterface $instance */
      $instance = $this->pluginManager->createInstance($definition['id']);
      if ($diff['needs_update'] = $instance->check($entity, $class)) {
        break;
      }
    }

    return $diff;
  }

  /**
   * Get cache items withing time frame.
   *
   * @param int $start
   *   Timestamp of start.
   * @param int $end
   *   Timestamp of end.
   *
   * @return array
   *   Array of Groupex IDs.
   */
  private function findByTimeFrame($start, $end) {
    $ids = [];

    $result = $this->queryFactory->get('groupex_google_cache')
      ->condition('field_gg_time_frame_start', $start, '>=')
      ->condition('field_gg_time_frame_start', $end, '<')
      ->execute();

    foreach ($result as $id) {
      $cache_item = GroupexGoogleCache::load($id);
      $id = $cache_item->field_gg_class_id->value;
      $ids[$id] = $id;
    }

    return $ids;
  }

  /**
   * Find base cache item by Groupex class ID.
   *
   * @param string $id
   *   Class ID.
   *
   * @return GroupexGoogleCacheInterface|bool
   *   Cache entity or FALSE.
   */
  protected function findParentEntityByClassId($id) {
    $result = $this->queryFactory->get('groupex_google_cache')
      ->condition('field_gg_class_id', $id)
      ->notExists('field_gg_parent_ref')
      ->execute();
    if (!empty($result)) {
      return GroupexGoogleCache::load(reset($result));
    }

    return FALSE;
  }

  /**
   * Return not pushed children.
   *
   * @param \Drupal\ymca_groupex_google_cache\Entity\GroupexGoogleCache $entity
   *   Parent cache entity.
   *
   * @return array
   *   List of child IDs, which were not pushed.
   */
  public function findChildrenNotPushed(GroupexGoogleCache $entity) {
    $ids = [];

    $result = $this->queryFactory->get('groupex_google_cache')
      ->condition('field_gg_parent_ref.target_id', $entity->id())
      ->notExists('field_gg_gcal_id')
      ->execute();

    if (!empty($result)) {
      $ids = array_values($result);
    }

    return $ids;
  }

  /**
   * Find all children by parent entity ID & sort by weight in reverse order.
   *
   * @param string $id
   *   Class ID.
   *
   * @return array
   *   List of child IDs. The bigger weight is higher.
   */
  public function findChildren($id) {
    $ids = [];

    $result = $this->queryFactory->get('groupex_google_cache')
      ->condition('field_gg_parent_ref.target_id', $id)
      ->execute();

    if (!empty($result)) {
      // Get weights in order to sort.
      $data = [];

      // Load entities in the safe way.
      $chunks = array_chunk($result, self::ENTITY_LOAD_CHUNK);
      foreach ($chunks as $chunk) {
        foreach ($this->cacheStorage->loadMultiple($chunk) as $entity) {
          $data[] = [
            'id' => $entity->id(),
            'weight' => $entity->field_gg_weight->value,
          ];
        }
      }

      // Sort by weight in reverse order.
      usort(
        $data,
        function ($a, $b) {
          return SortArray::sortByWeightElement($b, $a);
        }
      );

      // Extract IDs only.
      $ids = array_column($data, 'id');
    }

    return $ids;
  }

  /**
   * Build timestamps (start and end) for a class.
   *
   * @param string $date
   *   Date string. For example: "Tuesday, May 31, 2016".
   * @param string $time
   *   Time string. Example: "5:05am" or "All Day".
   *
   * @return array
   *   Array with start and ent timestamps.
   */
  public function buildTimestamps($date, $time) {
    $timestamps = [];

    $all_day = FALSE;
    preg_match("/(.*)-(.*)/i", $time, $output);
    if (isset($output[1]) && isset($output[2])) {
      $time_start = $output[1];
      $time_end = $output[2];
    }
    else {
      // If we can't fetch exact time, assume it as all day event.
      $all_day = TRUE;
      $time_start = '12:00pm';
      $time_end = '12:00pm';

      // Log exception for unknown values.
      if ($time != "All Day") {
        $message = 'DrupalProxy: Got unknown time value (%value)';
        $this->logger->error($message, ['%value' => $time]);
      }
    }

    $timestamps['start'] = $this->extractTime($date, $time_start);
    $timestamps['end'] = $this->extractTime($date, $time_end);

    // Just add 24 hours for All day events.
    if ($all_day) {
      $timestamps['end'] = $timestamps['start'] + (60 * 60 * 24);
    }

    return $timestamps;
  }

  /**
   * Extract timestamp from date and time strings.
   *
   * @param string $date
   *   Date string. Example: Tuesday, May 31, 2016.
   * @param string $time
   *   Time string. Example: 5:05am.
   *
   * @return int
   *   Timestamp.
   */
  public function extractTime($date, $time) {
    $dateTime = DrupalDateTime::createFromFormat(GroupexRequestTrait::$dateFullFormat, $date, $this->timezone);
    $start_datetime = new \DateTime($time);

    $dateTime->setTime(
      $start_datetime->format('H'),
      $start_datetime->format('i'),
      $start_datetime->format('s')
    );

    return $dateTime->getTimestamp();
  }

  /**
   * Creates DateTime object from entity fields.
   *
   * @param \Drupal\ymca_groupex_google_cache\GroupexGoogleCacheInterface $entity
   *   Cache entity.
   * @param string $item
   *   Start or End time. Example: 'start' or 'end'.
   * @param string $timezone
   *   Timezone. Example: 'UTC'.
   *
   * @return \DateTime|bool
   *   DateTime object or FALSE in case of parsing error.
   */
  public function extractEventDateTime(GroupexGoogleCacheInterface $entity, $item = 'start', $timezone = 'UTC') {
    if ($item != 'start' && $item != 'end') {
      return FALSE;
    }

    if (!$field_time = $entity->field_gg_time->value) {
      return FALSE;
    }

    if (!$field_date = $entity->field_gg_date_str->value) {
      return FALSE;
    }

    preg_match("/(.*)-(.*)/i", $field_time, $output);

    // Check if we've got start time.
    if ($item == 'start' && !isset($output[1])) {
      return FALSE;
    }

    // Check if we've got end time.
    if ($item == 'end' && !isset($output[2])) {
      return FALSE;
    }

    // Get time string. Example: 5:05am.
    $time = $item == 'start' ? $output[1] : $output[2];

    // Create DateTime object.
    $timezone = new \DateTimeZone($timezone);
    $dateObject = \DateTime::createFromFormat(GroupexRequestTrait::$dateFullFormat, $field_date, $timezone);

    // One more object just to grab time.
    $timeObject = new \DateTime($time, $timezone);
    $dateObject->setTime(
      $timeObject->format('H'),
      $timeObject->format('i'),
      $timeObject->format('s')
    );

    return $dateObject;
  }

}
