<?php

namespace Drupal\tzfield\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the time zone default widget.
 *
 * @FieldWidget(
 *   id = "tzfield_default",
 *   label = @Translation("Time zone"),
 *   field_types = {
 *     "tzfield"
 *   }
 * )
 */
class TimeZoneDefaultWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element['value'] = $element + [
      '#type' => 'select',
      '#options' => system_time_zones(!$element['#required'], TRUE),
      '#default_value' => isset($items[$delta]->value) ? $items[$delta]->value : NULL,
    ];
    return $element;
  }

}
