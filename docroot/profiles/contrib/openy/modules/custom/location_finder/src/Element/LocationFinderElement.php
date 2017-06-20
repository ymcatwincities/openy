<?php

namespace Drupal\location_finder\Element;

use Drupal\Core\Render\Element\RenderElement;

/**
 * Provides an element.
 *
 * @RenderElement("location_finder_element")
 */
class LocationFinderElement extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#theme' => 'location_finder_element',
      '#pre_render' => [
        [static::class, 'preRenderLocationFinderElement'],
      ],
    ];
  }

  /**
   * Prepare the render array for the template.
   */
  public static function preRenderLocationFinderElement($element) {
    return $element;
  }

}
