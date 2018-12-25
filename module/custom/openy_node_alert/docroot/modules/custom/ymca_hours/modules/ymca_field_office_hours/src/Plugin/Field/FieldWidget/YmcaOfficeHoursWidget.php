<?php

namespace Drupal\ymca_field_office_hours\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\ymca_field_office_hours\Plugin\Field\FieldType\YmcaOfficeHoursItem;

/**
 * Plugin implementation of the 'ymca_office_hours' widget.
 *
 * @FieldWidget(
 *   id = "ymca_office_hours_default",
 *   label = @Translation("YMCA office hours"),
 *   field_types = {
 *     "ymca_office_hours"
 *   }
 * )
 */
class YmcaOfficeHoursWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    /** @var YmcaOfficeHoursItem $item */
    $item = $items->get($delta);

    /** @var FieldConfig $definition */
    $definition = $item->getFieldDefinition();

    // Add field title.
    $element['title'] = [
      '#type' => 'html_tag',
      '#tag' => 'h3',
      '#value' => $definition->label(),
    ];

    foreach ($item::$days as $day) {
      $name = 'hours_' . $day;
      $hours = [
        '#title' => t('%day', ['%day' => ucfirst($day)]),
        '#type' => 'textfield',
        '#default_value' => isset($item->{$name}) ? $item->{$name} : '',
        '#description' => t('Example: 9am - 10pm'),
        '#required' => TRUE,
      ];
      $element['hours_' . $day] = $hours;
    }

    return $element;
  }

}
