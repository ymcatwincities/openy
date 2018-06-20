<?php

namespace Drupal\openy_daxko2\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Fills in link options with icon information.
 *
 * @MigrateProcessPlugin(
 *   id = "openy_daxko_schedule"
 * )
 */
class OpenYDaxkoSchedule extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (empty(json_decode($value))) {
      return [];
    }
    $value = json_decode($value);
//    {"times":[{"start":"18:00","end":"20:00"}],"days":[{"id":"5","name":"Friday"}],"start_date":"2018-02-16T00:00:00.0000000","end_date":"2018-02-16T00:00:00.0000000"}

    // Check for max timestamp date
    $timezone = new \DateTimeZone(drupal_get_user_timezone());
    $maxDt = new \DateTime();
    $maxDt->setTimestamp(2147483647);
    $maxDt->setTimezone($timezone);
    $maxDt->setTime(0, 0, 0, 0);
    $maxDate = $maxDt->format(DATETIME_DATETIME_STORAGE_FORMAT);
    // Set default start date as current time.
    $nowDt = new \DateTime();
    $nowDt->setTimezone($timezone);
    $currentDate = $nowDt->format(DATETIME_DATETIME_STORAGE_FORMAT);

    $paragraph = Paragraph::create(['type' => 'session_time' ]);

    $days = [];
    foreach ($value->days as $day) {
      $days[] = strtolower($day->name);
    }
    $paragraph->set('field_session_time_days', $days);

    $startDate = $currentDate;
    if (!empty($value->start_date)) {
      $startTime = !empty($value->times[0]) ? $value->times[0]->start : '00:00';
      $startDate = substr($value->start_date, 0, 11) . $startTime . ':00';
      if (strtotime($startDate) > 2147483647) {
        $startDate = $maxDate;
      }
      if (strtotime($startDate) <= 0) {
        $startDate = $currentDate;
      }
    }
    $endDate = $maxDate;
    if (!empty($value->end_date)) {
      $endTime = !empty($value->times[0]) ? $value->times[0]->end : '23:59';
      $endDate = substr($value->end_date, 0, 11) . $endTime . ':00';
      if (strtotime($endDate) > 2147483647) {
        $endDate = $maxDate;
      }
      if (strtotime($startDate) <= 0) {
        $endDate = $currentDate;
      }
    }
    $paragraph->set('field_session_time_date', ['value' => $startDate, 'end_value' => $endDate]);
    $paragraph->isNew();
    $paragraph->save();

    return [
      'target_id' => $paragraph->id(),
      'target_revision_id' => $paragraph->getRevisionId(),
    ];
  }

}
