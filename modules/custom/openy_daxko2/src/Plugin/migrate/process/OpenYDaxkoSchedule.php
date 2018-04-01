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
    $value = json_decode($value);
//    {"times":[{"start":"18:00","end":"20:00"}],"days":[{"id":"5","name":"Friday"}],"start_date":"2018-02-16T00:00:00.0000000","end_date":"2018-02-16T00:00:00.0000000"}

    $startDate = substr($value->start_date, 0, 11) . $value->times[0]->start . ':00';
    $endDate = substr($value->end_date, 0, 11) . $value->times[0]->end . ':00';

    $days = [];
    foreach ($value->days as $day) {
      $days[] = strtolower($day->name);
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
