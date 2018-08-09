<?php

namespace Drupal\openy_map\Element;

use Drupal\Core\Render\Element\RenderElement;

/**
 * Class OpenYMap.
 *
 * @RenderElement("openy_map")
 */
class OpenYMap extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#theme' => 'openy_map',
      '#show_controls' => FALSE,
      '#pre_render' => [
        [$class, 'processElement'],
      ],
    ];
  }

  /**
   * Prepare render array for template.
   *
   * @param array $element
   *   Element.
   *
   * @return array
   *   Element
   */
  public static function processElement(array $element) {
    $settings = \Drupal::configFactory()->get('openy_map.settings');
    $element['#attached']['library'][] = 'openy_map/openy_map';
    switch ($settings->get('map_engine')) {
      case 'gmaps':
        $element['#attached']['library'][] = 'openy_map/gmaps';
        $element['#attached']['drupalSettings']['openyMapSettings']['engine'] = 'gmaps';
      break;

      case 'leaflet':
      default:
        $element['#attached']['library'][] = 'openy_map/leaflet';
        $element['#attached']['drupalSettings']['openyMapSettings']['engine'] = 'leaflet';
        $element['#attached']['drupalSettings']['openyMapSettings']['default_location'] = urlencode(trim($settings->get('leaflet_location')));
        $element['#attached']['drupalSettings']['openyMapSettings']['search_icon'] = $settings->get('leaflet_search_icon');
        $element['#attached']['drupalSettings']['openyMapSettings']['search_icon_retina'] = $settings->get('leaflet_search_icon_retina');
      break;
    }
    $element['#attached']['drupalSettings']['openyMap'] = $element['#element_variables'];
    $tags = $settings->get('default_tags');
    $element['#attached']['drupalSettings']['openyMapSettings']['default_tags'] = array_values(array_filter($tags));
    $element['#cache']['tags'][] = 'config:openy_map.settings';

    return $element;
  }

}
