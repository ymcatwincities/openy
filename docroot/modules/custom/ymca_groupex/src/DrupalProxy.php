<?php

namespace Drupal\ymca_groupex;

use Drupal\ymca_google\GcalGroupexWrapper;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\ymca_mappings\Entity\Mapping;

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
   * DrupalProxy constructor.
   *
   * @param GcalGroupexWrapper $data_wrapper
   */
  public function __construct(GcalGroupexWrapper $data_wrapper) {
    $this->dataWrapper = $data_wrapper;
    $this->timezone = new \DateTimeZone('UTC');
  }

  /**
   * {@inheritdoc}
   */
  public function saveEntities() {
    $entities = [];

    foreach ($this->dataWrapper->getSourceData() as $item) {
      // Parse time to create timestamps.
      preg_match("/(.*)-(.*)/i", $item->time, $output);
      $item->timestamp_start = $this->extractTimestamp($item->date, $output[1]);
      $item->timestamp_end = $this->extractTimestamp($item->date, $output[2]);

      // Create entity.
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
        'field_timestamp_end' => $item->timestamp_end,
        'field_timestamp_start' => $item->timestamp_start,
      ]);
      $mapping->setName($item->location . ' [' . $item->id . ']');
      $mapping->save();

      $entities[] = $mapping;
    }

    $this->dataWrapper->setProxyData($entities);
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
