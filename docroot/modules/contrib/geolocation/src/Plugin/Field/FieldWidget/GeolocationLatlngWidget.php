<?php

namespace Drupal\geolocation\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\geolocation\GeolocationCore;

/**
 * Plugin implementation of the 'geolocation_latlng' widget.
 *
 * @FieldWidget(
 *   id = "geolocation_latlng",
 *   label = @Translation("Geolocation Lat/Lng"),
 *   field_types = {
 *     "geolocation"
 *   }
 * )
 */
class GeolocationLatlngWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $element['lat'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Latitude'),
      '#empty_value' => '',
      '#default_value' => (isset($items[$delta]->lat)) ? $items[$delta]->lat : NULL,
      '#maxlength' => 255,
      '#required' => $this->fieldDefinition->isRequired(),
    );
    if (!empty($element['lat']['#default_value'])) {
      $element['lat']['#description'] = $this->t('<span>Sexagesimal/DMS notation value: %sexagesimal</span>', ['%sexagesimal' => GeolocationCore::decimalToSexagesimal($element['lat']['#default_value'])]);
    }

    $element['lng'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Longitude'),
      '#empty_value' => '',
      '#default_value' => (isset($items[$delta]->lng)) ? $items[$delta]->lng : NULL,
      '#maxlength' => 255,
      '#required' => $this->fieldDefinition->isRequired(),
    );
    if (!empty($element['lng']['#default_value'])) {
      $element['lng']['#description'] = $this->t('<span>Sexagesimal/DMS notation value: %sexagesimal</span>', ['%sexagesimal' => GeolocationCore::decimalToSexagesimal($element['lng']['#default_value'])]);
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    // Grep GPS values and transform to regular float.
    foreach ($values as $index => $geolocation) {
      if (
        !empty($geolocation['lat'])
        && !empty($geolocation['lng'])
      ) {
        $latitude = GeolocationCore::sexagesimalToDecimal($values[$index]['lat']);
        $longitude = GeolocationCore::sexagesimalToDecimal($values[$index]['lng']);

        if (!empty($latitude) && !empty($longitude)) {
          $values[$index]['lat'] = $latitude;
          $values[$index]['lng'] = $longitude;
        }
      }
    }
    return parent::massageFormValues($values, $form, $form_state);
  }

}
