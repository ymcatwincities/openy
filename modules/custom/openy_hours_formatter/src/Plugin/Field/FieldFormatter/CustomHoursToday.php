<?php

namespace Drupal\openy_hours_formatter\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\openy_field_custom_hours\Plugin\Field\FieldFormatter\CustomHoursFormatterDefault;

/**
 * Plugin implementation for openy_custom_hours formatter.
 *
 * @FieldFormatter(
 *   id = "openy_today_custom_hours",
 *   label = @Translation("OpenY Today's hours"),
 *   field_types = {
 *     "openy_custom_hours"
 *   }
 * )
 */
class CustomHoursToday extends CustomHoursFormatterDefault {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elementsParent = parent::viewElements($items, $langcode);

    $lazy_hours = $lazy_hours_placeholder = [];
    foreach ($items as $delta => $item) {
      // Group days by their values.
      foreach ($item as $i_item) {
        $name = $i_item->getName();
        $day = str_replace('hours_', '', $name);
        $value = $i_item->getValue() ? $i_item->getValue() : 'closed';
        // Do not process label.
        if ($day != 'label') {
          $lazy_hours[$day] = $value;
        }
      }

      if ($delta == 0) {
        $lazy_hours_placeholder = [
          '#lazy_builder' => [
            'openy_hours_formatter.hours_today:generateHoursToday',
            $lazy_hours,
          ],
          '#create_placeholder' => TRUE,
        ];
      }
    }

    $elements[0] = [
      '#theme' => 'openy_hours_formatter',
      '#hours' => $lazy_hours_placeholder,
      '#week' => [
        '#theme' => 'item_list',
        '#attributes' => [
          'class' => [
            'branch-hours',
          ],
        ],
        '#items' => $elementsParent,
      ],
      '#attached' => [
        'library' => [
          'openy_hours_formatter/openy_hours_formatter',
        ],
      ],
    ];

    return $elements;
  }

}
