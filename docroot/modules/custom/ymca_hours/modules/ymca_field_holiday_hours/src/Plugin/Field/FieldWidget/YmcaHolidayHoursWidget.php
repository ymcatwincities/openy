<?php

/**
 * @file
 * Contains YMCA holiday hours widget.
 */

namespace Drupal\ymca_field_holiday_hours\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

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
      '#default_value' => isset($item->holiday) ? $item->holiday : ''
    ];

    $element['hours'] = [
      '#title' => t('Holiday hours'),
      '#type' => 'textfield',
      '#default_value' => isset($item->hours) ? $item->hours : ''
    ];

    $element['date'] = array(
      '#type' => 'date',
      '#title' => t('Date'),
      '#default_value' => isset($item->date) ? $item->date : ''
    );

    return $element;
  }

}
