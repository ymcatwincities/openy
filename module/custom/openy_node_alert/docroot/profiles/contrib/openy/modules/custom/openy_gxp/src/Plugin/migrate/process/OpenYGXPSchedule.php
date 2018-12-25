<?php

namespace Drupal\openy_gxp\Plugin\migrate\process;

use Drupal\migrate\Annotation\MigrateProcessPlugin;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\paragraphs\Entity\Paragraph;

/**
 * Parse time and build proper properties on Session fields.
 *
 * @MigrateProcessPlugin(
 *   id = "openy_gxp_schedule"
 * )
 */
class OpenYGXPSchedule extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $value = json_decode($value, TRUE);
//    {"times":[{"start":"18:00","end":"20:00"}],"days":["Friday"],"start_date":"2018-02-16","end_date":"2018-02-16"}

    // Convert to UTC timezone to save to database.
    $siteTimezone = new \DateTimeZone(drupal_get_user_timezone());
    $gmtTimezone = new \DateTimeZone('GMT');

    $startTime = new \DateTime($value['start_date'] . ' ' . $value['times']['start'] . ':00', $siteTimezone);
    $startTime->setTimezone($gmtTimezone);

    $endTime = new \DateTime($value['end_date'] . ' ' . $value['times']['end'] . ':00', $siteTimezone);
    $endTime->setTimezone($gmtTimezone);

    $startDate = $startTime->format(DATETIME_DATETIME_STORAGE_FORMAT);
    $endDate = $endTime->format(DATETIME_DATETIME_STORAGE_FORMAT);

    $days = [];
    foreach ($value['days'] as $day) {
      $days[] = strtolower($day);
    }

    if (empty($day)) {
      return;
    }

    $paragraph = Paragraph::create(['type' => 'session_time' ]);
    $paragraph->set('field_session_time_days', $days);
    $paragraph->set('field_session_time_date', ['value' => $startDate, 'end_value' => $endDate]);
    $paragraph->isNew();
    $paragraph->save();

    return [
      'target_id' => $paragraph->id(),
      'target_revision_id' => $paragraph->getRevisionId(),
    ];
  }

}
