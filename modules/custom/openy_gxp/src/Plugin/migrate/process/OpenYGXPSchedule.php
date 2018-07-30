<?php

namespace Drupal\openy_gxp\Plugin\migrate\process;

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

    $startDate = $value['start_date'] . 'T' . $value['times']['start'] . ':00';
    $endDate = $value['end_date'] . 'T' . $value['times']['end'] . ':00';

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
