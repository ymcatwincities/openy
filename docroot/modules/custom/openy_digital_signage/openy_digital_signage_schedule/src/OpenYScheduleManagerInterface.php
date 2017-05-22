<?php

namespace Drupal\openy_digital_signage_schedule;

use Drupal\openy_digital_signage_schedule\Entity\OpenYSchedule;

/**
 * Interface OpenYScheduleManagerInterface.
 *
 * @package Drupal\openy_digital_signage_schedule
 */
interface OpenYScheduleManagerInterface {

  /**
   * Dummy method.
   */
  public function dummy();

  /**
   * Returns upcoming schedule items and its content entities.
   *
   * @param \Drupal\openy_digital_signage_schedule\Entity\OpenYSchedule $schedule
   *   Schedule to work with.
   * @param int $timespan
   *   Timespan lenght in seconds.
   * @param int $now
   *   Current timestamp.
   *
   * @return mixed
   *   Array of Screen Content nodes or null.
   */
  public function getUpcomingScreenContents(OpenYSchedule $schedule, $timespan, $now = NULL);

}
