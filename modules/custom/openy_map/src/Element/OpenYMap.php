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

    $element['#attached']['library'][] = 'openy_map/openy_map';
    $element['#attached']['drupalSettings']['openyMap'] = $element['#element_variables'];
    $tags = \Drupal::configFactory()->get('openy_map.settings')->get('default_tags');
    $element['#attached']['drupalSettings']['openyMapSettings']['default_tags'] = array_values(array_filter($tags));
    $element['#cache']['tags'][] = 'config:openy_map.settings';

    return $element;
  }

}
