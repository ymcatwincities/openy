<?php

namespace Drupal\geolocation\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElementInterface;

/**
 * Provides a one-line text field form element.
 *
 * @FormElement("geolocation_google_map_input")
 */
class GeolocationGoogleMapInput extends GeolocationGoogleMap implements FormElementInterface {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $get_info = parent::getInfo();
    unset($get_info['#pre_render']);
    $get_info['#input'] = TRUE;
    $get_info['#max_locations'] = 1;
    $get_info['#process'] = [
      [$this, 'processMapInputElement'],
    ];
    $get_info['#theme_wrappers'] = ['container'];
    $get_info['#attributes']['class'] = [
      'geolocation-google-map-form-element',
    ];
    $get_info['#attached']['library'] = ['geolocation/geolocation.google_map_form_element'];

    return $get_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function valueCallback(&$element, $input, FormStateInterface $form_state) {
    return $input;
  }

  /**
   * Mapping input.
   *
   * @param array $element
   *   Element.
   *
   * @return array
   *   Renderable form element.
   */
  public function processMapInputElement(array &$element) {
    $element['#tree'] = TRUE;

    $max_locations = 1;
    if (!empty($element['#max_locations'])) {
      $max_locations = (int) $element['#max_locations'];
    }

    if (!empty($element['#locations'])) {
      $element['#locations'] = array_splice($element['#locations'], 0, $max_locations);
    }

    $element['geolocation_map'] = $this->preRenderGoogleMapElement($element);

    $element['#theme_wrappers']['container']['#attributes']['id'] = 'geolocation-google-map-form-element-' . $element['geolocation_map']['#uniqueid'];

    $element['#attached']['drupalSettings'] = [
      'geolocation' => [
        'googleMapFormElements' => [
          $element['geolocation_map']['#uniqueid'] => [
            'maxLocations' => $max_locations,
          ],
        ],
      ],
    ];

    for ($i = 0; $i < $max_locations; $i++) {
      $element['geolocation_map_input_' . $i] = [
        '#type' => 'fieldset',
        '#attributes' => [
          'class' => [
            'geolocation-map-input',
            'geolocation-map-input-' . $i,
          ],
        ],
        'latitude' => [
          '#type' => 'textfield',
          '#title' => $this->t('Latitude'),
          '#attributes' => [
            'class' => [
              'geolocation-map-input-latitude',
            ],
          ],
          '#value' => empty($element['#locations'][$i]) ? '' : $element['#locations'][$i]['latitude'],
        ],
        'longitude' => [
          '#type' => 'textfield',
          '#title' => $this->t('Longitude'),
          '#attributes' => [
            'class' => [
              'geolocation-map-input-longitude',
            ],
          ],
          '#value' => empty($element['#locations'][$i]) ? '' : $element['#locations'][$i]['longitude'],
        ],
      ];

      if (!empty($element['#title'])) {
        if ($max_locations > 1) {
          $element['geolocation_map_input_' . $i]['#title'] = $element['#title'] . ' #' . $i;
        }
        else {
          $element['geolocation_map_input_' . $i]['#title'] = $element['#title'];
        }
      }
    }

    return $element;
  }

}
