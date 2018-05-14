<?php

namespace Drupal\geolocation\Plugin\Geocoder\Field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldConfigInterface;
use Drupal\geocoder_field\Plugin\Geocoder\Field\DefaultField;

/**
 * Provides a geolocation geocoder field plugin.
 *
 * @GeocoderField(
 *   id = "geolocation",
 *   label = @Translation("Geolocation field plugin"),
 *   field_types = {
 *     "geolocation"
 *   }
 * )
 */
class Geolocation extends DefaultField {

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm(FieldConfigInterface $field, array $form, FormStateInterface &$form_state) {
    $element = parent::getSettingsForm($field, $form, $form_state);
    // Hard-wire the dumper for geolocation fields.
    $element['dumper'] = [
      '#type' => 'value',
      '#value' => 'geolocation',
    ];
    return $element;
  }

}
