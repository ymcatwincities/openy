<?php

namespace Drupal\address\Plugin\Field\FieldWidget;

use CommerceGuys\Addressing\Zone\Zone;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Plugin implementation of the 'address_zone_default' widget.
 *
 * @FieldWidget(
 *   id = "address_zone_default",
 *   label = @Translation("Zone"),
 *   field_types = {
 *     "address_zone"
 *   },
 * )
 */
class ZoneDefaultWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'show_label_field' => FALSE,
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = [];
    $element['show_label_field'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show the zone label field'),
      '#default_value' => $this->getSetting('show_label_field'),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    $summary['show_label_field'] = $this->t('Zone label field: @status', [
      '@status' => $this->getSetting('show_label_field') ? $this->t('Shown') : $this->t('Hidden'),
    ]);

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $item = $items[$delta];
    $value = [];
    if (!$item->isEmpty()) {
      /** @var \CommerceGuys\Addressing\Zone\Zone $zone */
      $zone = $item->value;
      $value = [
        'label' => $zone->getLabel(),
        'territories' => [],
      ];
      foreach ($zone->getTerritories() as $territory) {
        $value['territories'][] = [
          'country_code' => $territory->getCountryCode(),
          'administrative_area' => $territory->getAdministrativeArea(),
          'locality' => $territory->getLocality(),
          'dependent_locality' => $territory->getDependentLocality(),
          'included_postal_codes' => $territory->getIncludedPostalCodes(),
          'excluded_postal_codes' => $territory->getExcludedPostalCodes(),
        ];
      }
    }

    $element += [
      '#type' => 'details',
      '#collapsible' => TRUE,
      '#open' => TRUE,
    ];
    $element['zone'] = [
      '#type' => 'address_zone',
      '#default_value' => $value,
      '#required' => $this->fieldDefinition->isRequired(),
      '#show_label_field' => $this->getSetting('show_label_field'),
      '#available_countries' => $item->getAvailableCountries(),
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function errorElement(array $element, ConstraintViolationInterface $violation, array $form, FormStateInterface $form_state) {
    $error_element = NestedArray::getValue($element['zone'], $violation->arrayPropertyPath);
    return is_array($error_element) ? $error_element : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $new_values = [];
    foreach ($values as $delta => $value) {
      if (empty($value['zone']['territories'])) {
        // Zones with no territories are considered empty.
        continue;
      }

      $new_values[$delta] = new Zone([
        'id' => $this->fieldDefinition->getName(),
        'label' => $value['zone']['label'] ?: $this->fieldDefinition->getLabel(),
        'territories' => $value['zone']['territories'],
      ]);
    }
    return $new_values;
  }

}
