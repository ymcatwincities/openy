<?php

namespace Drupal\openy_field_holiday_hours\Plugin\Field\FieldWidget;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'openy_holiday_hours' widget.
 *
 * @FieldWidget(
 *   id = "openy_holiday_hours_default",
 *   label = @Translation("OpenY Holiday Hours"),
 *   field_types = {
 *     "openy_holiday_hours"
 *   }
 * )
 */
class HolidayHoursWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $item = $items->get($delta);

    $element['holiday'] = [
      '#title' => t('Holiday title'),
      '#type' => 'textfield',
      '#default_value' => isset($item->holiday) ? $item->holiday : '',
      '#description' => t('Example: Thanksgiving Day. To remove entire section clear this field and hit Save'),
    ];

    $element['hours'] = [
      '#title' => t('Holiday hours'),
      '#type' => 'textfield',
      '#default_value' => isset($item->hours) ? $item->hours : '',
      '#description' => t('Example: 1pm - 2pm'),
    ];

    // Set default value.
    $element['date'] = array(
      '#type' => 'datetime',
      '#title' => t('Date'),
      '#default_value' => isset($item->date) ? DrupalDateTime::createFromTimestamp($item->date) : '',
      '#date_time_element' => 'none',
    );

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as &$item) {
      if (!is_null($item['date']) && $item['date'] instanceof DrupalDateTime) {
        // Here we need to save the date with 00:00:00 UTC.
        $item['date'] = strtotime($item['date']->format('d-m-Y'));
      }
      else {
        $item['date'] = NULL;
      }
    }
    return $values;
  }

}
