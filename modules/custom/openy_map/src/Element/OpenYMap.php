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
        $mapSettings = &$element['#attached']['drupalSettings']['openyMapSettings'];
        $mapSettings['engine'] = 'leaflet';
        $mapSettings['default_location'] = urlencode(trim(_openy_map_get_default_location()));
        $mapSettings['search_icon'] = $settings->get('leaflet.search_icon');
        $mapSettings['search_icon_retina'] = $settings->get('leaflet.search_icon_retina');
        $mapSettings['base_layer'] = $settings->get('leaflet.base_layer');
        $mapSettings['base_layer_override'] = $settings->get('leaflet.base_layer_override');
        $element['#attached']['drupalSettings']['openyMapSettings']['leaflet_clustering'] = $settings->get('leaflet.clustering');
        $element['#attached']['library'][] = 'openy_map/leaflet.markercluster';
        break;
    }
    $element['#attached']['drupalSettings']['openyMap'] = $element['#element_variables'];
    $tags = $settings->get('default_tags');
    $element['#attached']['drupalSettings']['openyMapSettings']['default_tags'] = array_values(array_filter($tags));
    $element['#cache']['tags'][] = 'config:openy_map.settings';

    return $element;
  }

}
