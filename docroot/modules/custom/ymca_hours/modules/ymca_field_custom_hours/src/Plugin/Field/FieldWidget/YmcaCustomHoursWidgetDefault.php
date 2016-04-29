<?php

namespace Drupal\ymca_field_custom_hours\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\ymca_field_custom_hours\Plugin\Field\FieldType\YmcaCustomHoursItem;

/**
 * Plugin implementation for ymca_custom_hours widget.
 *
 * @FieldWidget(
 *   id = "ymca_custom_hours_default",
 *   label = @Translation("YMCA custom hours"),
 *   field_types = {
 *     "ymca_custom_hours"
 *   }
 * )
 */
class YmcaCustomHoursWidgetDefault extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    /** @var YmcaCustomHoursItem $item */
    $item = $items->get($delta);

    /** @var FieldConfig $definition */
    $definition = $item->getFieldDefinition();

    // Add field title.
    $element['title'] = [
      '#type' => 'html_tag',
      '#tag' => 'h3',
      '#value' => $definition->label(),
    ];

    $element['hours_label'] = [
      '#title' => t('Custom hours label'),
      '#type' => 'textfield',
      '#default_value' => isset($item->hours_label) ? $item->hours_label : '',
      '#description' => t('To remove entire section clear this field and click Save.')
    ];

    foreach ($item::$days as $day) {
      $name = 'hours_' . $day;
      $hours = [
        '#title' => t('%day', ['%day' => ucfirst($day)]),
        '#type' => 'textfield',
        '#default_value' => isset($item->{$name}) ? $item->{$name} : '',
        '#placeholder' => t('Example: 9am - 10pm'),
      ];
      $element['hours_' . $day] = $hours;
    }

    return $element;
  }

}
