<?php

namespace Drupal\geolocation\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\geolocation\GeolocationCore;

/**
 * Plugin implementation of the 'geolocation_sexagesimal' formatter.
 *
 * @FieldFormatter(
 *   id = "geolocation_sexagesimal",
 *   module = "geolocation",
 *   label = @Translation("Geolocation Sexagesimal / GPS / DMS"),
 *   field_types = {
 *     "geolocation"
 *   }
 * )
 */
class GeolocationSexagesimalFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = array();

    foreach ($items as $delta => $item) {
      $element[$delta] = array(
        '#theme' => 'geolocation_sexagesimal_formatter',
        '#lat' => GeolocationCore::decimalToSexagesimal($item->lat),
        '#lng' => GeolocationCore::decimalToSexagesimal($item->lng),
      );
    }

    return $element;
  }

}
