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
      'monday',
      'tuesday',
      'wednesday',
      'thursday',
      'friday',
      'saturday',
      'sunday',
    ];
    $weekdays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
    $weekend = ['saturday', 'sunday'];
    $count = 0;
    // Check if weekdays have the same working time.
    foreach ($render_items as $delta => $item) {
      if ($item['#field_collection_item']->field_branch_hours_day->value && $item['#field_collection_item']->field_branch_hours_time->value) {
        if (in_array($item['#field_collection_item']->field_branch_hours_day->value, $weekdays) && empty($time)) {
          $time = $item['#field_collection_item']->field_branch_hours_time->value;
        }
        if (in_array($item['#field_collection_item']->field_branch_hours_day->value, $weekdays) && $time == $item['#field_collection_item']->field_branch_hours_time->value) {
          $count++;
        }
      }
    }

    foreach ($render_items as $delta => $item) {
      if ($item['#field_collection_item']->field_branch_hours_day->value && $item['#field_collection_item']->field_branch_hours_time->value) {
        $day = $item['#field_collection_item']->field_branch_hours_day->value;
        $lazy_hours[array_search($day, $days)] = $item['#field_collection_item']->field_branch_hours_time->value;
        // This means we have the same working time during work week.
        if ($count == 5) {
          $week['Monday - Friday'] = $time;
          if (in_array($day, $weekend)) {
            $week[ucfirst($day)] = $item['#field_collection_item']->field_branch_hours_time->value;
          }
        }
        else {
          $week[ucfirst($day)] = $item['#field_collection_item']->field_branch_hours_time->value;
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
