<?php

namespace Drupal\ymca_field_holiday_hours\Plugin\Field\FieldWidget;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ymca_field_office_hours\Plugin\Field\FieldType\YmcaOfficeHoursItem;

/**
 * Plugin implementation of the 'ymca_holiday_hours' widget.
 *
 * @FieldWidget(
 *   id = "ymca_holiday_hours_default",
 *   label = @Translation("YMCA holiday hours"),
 *   field_types = {
 *     "ymca_holiday_hours"
 *   }
 * )
 */
class YmcaHolidayHoursWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    /** @var YmcaOfficeHoursItem $item */
    $item = $items->get($delta);

    $element['holiday'] = [
      '#title' => t('Holiday title'),
      '#type' => 'textfield',
      '#default_value' => isset($item->holiday) ? $item->holiday : '',
      '#description' => t('Example: Thanksgiving Day'),
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
