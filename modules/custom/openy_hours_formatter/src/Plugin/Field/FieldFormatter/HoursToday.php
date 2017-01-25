<?php

namespace Drupal\openy_hours_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\field_collection\Plugin\Field\FieldFormatter\FieldCollectionItemsFormatter;

/**
 * Plugin implementation of the 'openy_hours_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "openy_hours_formatter",
 *   label = @Translation("Today's hours"),
 *   field_types = {
 *     "field_collection"
 *   }
 * )
 */
class HoursToday extends FieldCollectionItemsFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $render_items = parent::viewElements($items, $langcode);

    $time = '';
    $lazy_hours = [];
    $week = [];
    $days = [
      'Monday',
      'Tuesday',
      'Wednesday',
      'Thursday',
      'Friday',
      'Saturday',
      'Sunday',
    ];
    $weekdays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
    $weekend = ['Saturday', 'Sunday'];
    $count = 0;
    // Check if weekdays have the same working time.
    foreach ($render_items as $delta => $item) {
      if (isset($item['field_day_of_the_week'][0]['#markup']) && isset($item['field_start_end_time'][0]['#context']['value'])) {
        if (in_array($item['field_day_of_the_week'][0]['#markup'], $weekdays) && empty($time)) {
          $time = $item['field_start_end_time'][0]['#context']['value'];
        }
        if (in_array($item['field_day_of_the_week'][0]['#markup'], $weekdays) && $time == $item['field_start_end_time'][0]['#context']['value']) {
          $count++;
        }
      }
    }

    foreach ($render_items as $delta => $item) {
      if (isset($item['field_day_of_the_week'][0]['#markup']) && isset($item['field_start_end_time'][0]['#context']['value'])) {
        $day = $item['field_day_of_the_week'][0]['#markup'];
        $lazy_hours[array_search($day, $days)] = $item['field_start_end_time'][0]['#context']['value'];
        // This means we have the same working time during work week.
        if ($count == 5) {
          $week['Monday - Friday'] = $time;
          if (in_array($day, $weekend)) {
            $week[$day] = $item['field_start_end_time'][0]['#context']['value'];
          }
        }
        else {
          $week[$day] = $item['field_start_end_time'][0]['#context']['value'];
        }
      }
    }

    $lazy_hours_placeholder = [
      '#lazy_builder' => [
        'openy_hours_formatter.hours_today:generateHoursToday',
        $lazy_hours,
      ],
      '#create_placeholder' => TRUE,
    ];

    $render_array = [
      '#theme' => 'openy_hours_formatter',
      '#hours' => $lazy_hours_placeholder,
      '#week' => $week,
      '#attached' => [
        'library' => [
          'openy_hours_formatter/openy_hours_formatter',
        ],
      ],
    ];
    return $render_array;
  }

}
