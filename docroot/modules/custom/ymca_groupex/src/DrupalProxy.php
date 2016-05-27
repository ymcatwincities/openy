<?php

namespace Drupal\ymca_groupex;

use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Logger\LoggerChannelFactory;
use Drupal\ymca_google\GcalGroupexWrapper;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\ymca_mappings\Entity\Mapping;
use Drupal\ymca_mappings\MappingInterface;

/**
 * Class DrupalProxy.
 *
 * @package Drupal\ymca_groupex
 */
class DrupalProxy implements DrupalProxyInterface {

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
   * Logger factory.
   *
   * @var QueryFactory
   */
  protected $loggerFactory;

  /**
   * DrupalProxy constructor.
   *
   * @param GcalGroupexWrapper $data_wrapper
   *   Data wrapper.
   * @param QueryFactory $query_factory
   *   Query factory.
   * @param LoggerChannelFactory $logger_factory
   *   Logger factory.
   */
  public function __construct(GcalGroupexWrapper $data_wrapper, QueryFactory $query_factory, LoggerChannelFactory $logger_factory) {
    $this->dataWrapper = $data_wrapper;
    $this->queryFactory = $query_factory;
    $this->loggerFactory = $logger_factory;

    $this->timezone = new \DateTimeZone('UTC');
  }

  /**
   * {@inheritdoc}
   */
  public function saveEntities() {
    $entities = [];

    foreach ($this->dataWrapper->getSourceData() as $item) {
      // Parse time to create timestamps.
      $all_day = FALSE;
      preg_match("/(.*)-(.*)/i", $item->time, $output);
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
        if ($item->time != "All Day") {
          $message = 'DrupalProxy: Got unknown time value (%value) for the class ID %ID';
          $this->loggerFactory->get('ymca_sync')->error(
            $message,
            ['%value' => $item->time, '%ID' => $item->id]
          );
        }
      }

      $item->timestamp_start = $this->extractTimestamp($item->date, $time_start);
      $item->timestamp_end = $this->extractTimestamp($item->date, $time_end);

      // Just add 24 hours for All day events.
      if ($all_day) {
        $item->timestamp_end = $item->timestamp_start + (60 * 60 * 24);
      }

      // Try to find existing mapping.
      $existing = $this->findByGroupexId($item->id);

      // Create entity, if ID doesn't exist.
      if (!$existing) {
        $mapping = Mapping::create([
          'type' => 'groupex',
          'field_groupex_category' => $item->category,
          'field_groupex_class_id' => $item->id,
          'field_groupex_date' => [$item->date],
          'field_groupex_description' => $item->desc,
          'field_groupex_instructor' => $item->instructor,
          'field_groupex_location' => $item->location,
          'field_groupex_orig_instructor' => $item->original_instructor,
          'field_groupex_studio' => $item->studio,
          'field_groupex_sub_instructor' => $item->sub_instructor,
          'field_groupex_time' => $item->time,
          'field_groupex_title' => $item->title,
          'field_timestamp_end' => $item->timestamp_end,
          'field_timestamp_start' => $item->timestamp_start,
        ]);
        $mapping->setName($item->location . ' [' . $item->id . ']');
        $mapping->save();
        $entities['insert'][] = $mapping;
      }
      else {
        // Entity exists. Check for the diff.
        $diff = $this->diff($existing, $item);

        // Proceed only with changed entities.
        if (!empty($diff['date']) || !empty($diff['fields'])) {

          // Update fields.
          foreach ($diff['fields'] as $field_name => $value) {
            $existing->set($field_name, $value);
          }

          // Add recurring event.
          if (!empty($diff['date'])) {

          }

          $existing->save();
          $entities['update'][] = $existing;
        }
      }

    }

    $this->dataWrapper->setProxyData($entities);
  }

  /**
   * Diffs entity saved in DB and groupex class item.
   *
   * @param MappingInterface $entity
   *   Mapping entity.
   *
   * @param $class
   *   Class item (enriched with timestamps).
   *
   * @return mixed
   *   Diff array.
   */
  protected function diff(MappingInterface $entity, $class) {
    $diff['fields'] = [];
    $diff['date'] = [];

    // Compare simple fields.
    $compare = [
      'field_groupex_category' => 'category',
      'field_groupex_description' => 'desc',
      'field_groupex_instructor' => 'instructor',
      'field_groupex_location' => 'location',
      'field_groupex_orig_instructor' => 'original_instructor',
      'field_groupex_studio' => 'studio',
      'field_groupex_sub_instructor' => 'sub_instructor',
      'field_groupex_title' => 'title',
      'field_groupex_time' => 'time',
    ];

    foreach ($compare as $drupal_field => $groupex_field) {
      $drupal_value = $entity->{$drupal_field}->value;
      $groupex_value = $class->{$groupex_field};
      if (strcmp($drupal_value, $groupex_value) != 0) {
        $diff['fields'][$drupal_field] = $groupex_value;
      }
    }

    // Check timestamps.
    if (
      $entity->field_timestamp_start->value == $class->timestamp_start &&
      $entity->field_timestamp_end->value == $class->timestamp_end
    ) {
      return $diff;
    }

    // The event is recurring.
    $diff['date']['date'] = $class->date;
    $diff['date']['time'] = $class->time;

    return $diff;
  }

  /**
   * Find mapping by Groupex class ID.
   *
   * @param string $id
   *   Groupex class ID.
   *
   * @return Mapping
   *   Mapping entity.
   */
  public function findByGroupexId($id) {
    $result = $this->queryFactory->get('mapping')
      ->condition('field_groupex_class_id', $id)
      ->execute();
    if (!empty($result)) {
      return Mapping::load(reset($result));
    }
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
  private function extractTimestamp($date, $time) {
    $dateTime = DrupalDateTime::createFromFormat(GroupexRequestTrait::$dateFullFormat, $date, $this->timezone);
    $start_datetime = new \DateTime($time);

    $dateTime->setTime(
      $start_datetime->format('H'),
      $start_datetime->format('i'),
      $start_datetime->format('s')
    );

    return $dateTime->getTimestamp();
  }

}
