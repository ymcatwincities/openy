<?php

namespace Drupal\openy_hours_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceEntityFormatter;

/**
 * Plugin implementation of the 'openy_hours_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "openy_branch_hours_formatter",
 *   label = @Translation("Branch hours"),
 *   field_types = {
 *     "entity_reference_revisions"
 *   }
 * )
 */
class BranchHours extends EntityReferenceEntityFormatter {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $render_items = parent::viewElements($items, $langcode);
    $time = $hours = '';
    $week = [];
    $weekdays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
    $weekend = ['saturday', 'sunday'];
    $count = 0;
    // Check if weekdays have the same working time.
    foreach ($render_items as $delta => $item) {
      if ($item['#paragraph']->field_branch_hours_day->value && $item['#paragraph']->field_branch_hours_time->value) {
        if (in_array($item['#paragraph']->field_branch_hours_day->value, $weekdays) && empty($time)) {
          $time = $item['#paragraph']->field_branch_hours_time->value;
        }
        if (in_array($item['#paragraph']->field_branch_hours_day->value, $weekdays) && $time == $item['#paragraph']->field_branch_hours_time->value) {
          $count++;
        }
      }
    }

    foreach ($render_items as $delta => $item) {
      if ($item['#paragraph']->field_branch_hours_day->value && $item['#paragraph']->field_branch_hours_time->value) {
        $day = $item['#paragraph']->field_branch_hours_day->value;
        // This means we have the same working time during work week.
        if ($count == 5) {
          $week['Monday - Friday'] = $time;
          if (in_array($day, $weekend)) {
            $week[ucfirst($day)] = $item['#paragraph']->field_branch_hours_time->value;
          }
        }
        else {
          $week[ucfirst($day)] = $item['#paragraph']->field_branch_hours_time->value;
        }
      }
    }

    $render_array = [
      '#theme' => 'openy_branch_hours_formatter',
      '#week' => $week,
    ];
    return $render_array;
  }

}
