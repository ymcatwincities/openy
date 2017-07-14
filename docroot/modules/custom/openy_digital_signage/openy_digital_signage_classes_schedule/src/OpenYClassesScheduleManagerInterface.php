<?php

namespace Drupal\openy_digital_signage_classes_schedule;
use Drupal\Core\Entity\EntityInterface;

/**
 * Interface OpenYClassesScheduleManagerInterface.
 *
 * @ingroup openy_digital_signage_classes_schedule
 */
interface OpenYClassesScheduleManagerInterface {

  /**
   * Dummy method.
   */
  public function dummy();

  /**
   * Retrieves the schedule for given time period and location.
   *
   * @param array $period
   *   Associative array with from and to keys.
   * @param \Drupal\Core\Entity\EntityInterface $location
   *   The reference to location
   * @param string $room
   *   The room name.
   *
   * @return array
   *   The array of scheduled classes.
   */
  public function getClassesSchedule($period, EntityInterface $location, $room);

}
