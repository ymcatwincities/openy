<?php

namespace Drupal\geolocation\Element;

use Drupal\Core\Render\Element\RenderElement;
use Drupal\geolocation\GoogleMapsDisplayTrait;

/**
 * Provides a render element to display a geolocation map.
 *
 * Usage example:
 * @code
 * $form['map'] = [
 *   '#type' => 'geolocation_google_map',
 *   '#prefix' => $this->t('Geolocation Google Map Render Element'),
 *   '#description' => $this->t('Render element type "geolocation_google_map"'),
 *   '#longitude' => 42,
 *   '#latitude' => 34,
 *   '#width' => 100,
 *   '#height' => 100,
 *   '#zoom' => 4,
 *   '#controls' => FALSE,
 * ];
 * @endcode
 *
 * @FormElement("geolocation_google_map")
 */
class GeolocationGoogleMap extends RenderElement {

  use GoogleMapsDisplayTrait;

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#pre_render' => [
        [$this, 'preRenderGoogleMapElement'],
      ],
      '#latitude' => NULL,
      '#longitude' => NULL,
      '#locations' => NULL,
      '#height' => NULL,
      '#width' => NULL,
      '#zoom' => NULL,
      '#controls' => FALSE,
    ];
  }

  /**
   * Map element.
   *
   * @param array $element
   *   Element.
   *
   * @return array
   *   Renderable map.
   */
  public function preRenderGoogleMapElement(array $element) {

    $render_array = [
      '#theme' => 'geolocation_map_formatter',
      '#attached' => [
        'library' => ['geolocation/geolocation.formatter.googlemap'],
        'drupalSettings' => [
          'geolocation' => [
            'google_map_url' => $this->getGoogleMapsApiUrl(),
          ],
        ],
      ],
    ];

    if (!empty($element['#prefix'])) {
      $render_array['#prefix'] = $element['#prefix'];
    }

    if (!empty($element['#suffix'])) {
      $render_array['#suffix'] = $element['#suffix'];
    }

    $settings = $this->getGoogleMapDefaultSettings();
    if (!empty($element['#settings'])) {
      $settings = array_replace_recursive($settings, $element['#settings']);
    }
    $settings['google_map_settings']['info_auto_display'] = FALSE;

    if (!empty($element['#height'])) {
      $settings['google_map_settings']['height'] = (int) $element['#height'] . 'px';
    }

    if (!empty($element['#width'])) {
      $settings['google_map_settings']['width'] = (int) $element['#width'] . 'px';
    }

    if (!empty($element['#zoom'])) {
      $settings['google_map_settings']['zoom'] = (int) $element['#zoom'];
    }

    if (empty($element['#controls'])) {
      $settings['google_map_settings']['streetViewControl'] = FALSE;
      $settings['google_map_settings']['mapTypeControl'] = FALSE;
      $settings['google_map_settings']['rotateControl'] = FALSE;
      $settings['google_map_settings']['fullscreenControl'] = FALSE;
      $settings['google_map_settings']['zoomControl'] = FALSE;
    }

    $unique_id = uniqid("map-canvas-");

    if (empty($element['#locations'])) {
      $render_array['#latitude'] = $element['#latitude'];
      $render_array['#longitude'] = $element['#longitude'];
      $render_array['#uniqueid'] = $unique_id;
      $render_array['#attached']['drupalSettings']['geolocation']['maps'][$unique_id] = [
        'settings' => $settings,
      ];
    }
    else {
      $locations = [];

      foreach ($element['#locations'] as $delta => $item) {

        $fallback = $item['latitude'] . ' ' . $item['longitude'];

        $locations[] = [
          '#theme' => 'geolocation_common_map_location',
          '#content' => empty($item['content']) ? $fallback : $item['content'],
          '#title' => empty($item['title']) ? $fallback : $item['title'],
          '#position' => [
            'lat' => $item['latitude'],
            'lng' => $item['longitude'],
          ],
        ];
      }

      $render_array['#locations'] = $locations;
      $render_array['#uniqueid'] = $unique_id;
      $render_array['#attached']['drupalSettings']['geolocation']['maps'][$unique_id] = [
        'settings' => $settings,
      ];
    }

    return $render_array;
  }

}
