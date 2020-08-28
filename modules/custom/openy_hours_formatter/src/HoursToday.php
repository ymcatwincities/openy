<?php

namespace Drupal\openy_hours_formatter;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Contains HoursToday class.
 */
class HoursToday implements TrustedCallbackInterface{

  /**
   * Get today hours.
   *
   * @return array
   *   Renderable array of today hours.
   */
  public function generateHoursToday() {
    $request_time = \Drupal::time()->getRequestTime();
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
    $date = DrupalDateTime::createFromTimestamp($request_time, $timezone);
    $today = $date->format('l');

    return [
      '#markup' => '<span class="today">' . $hours[array_search($today, $days)] . '</span>',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['generateHoursToday'];
  }

}
