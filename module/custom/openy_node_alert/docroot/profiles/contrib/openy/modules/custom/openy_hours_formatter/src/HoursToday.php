<?php

namespace Drupal\openy_hours_formatter;

use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Contains HoursToday class.
 */
class HoursToday {

  /**
   * Get today hours.
   *
   * @return array
   *   Renderable array of today hours.
   */
  public function generateHoursToday() {
    $hours = func_get_args();
    $days = [
      'Monday',
      'Tuesday',
      'Wednesday',
      'Thursday',
      'Friday',
      'Saturday',
      'Sunday',
    ];
    $timezone = new \DateTimeZone(\Drupal::config('system.date')->get('timezone')['default']);
    $date = DrupalDateTime::createFromTimestamp(REQUEST_TIME, $timezone);
    $today = $date->format('l');

    return [
      '#markup' => '<span class="today">' . $hours[array_search($today, $days)] . '</span>',
    ];
  }

}
