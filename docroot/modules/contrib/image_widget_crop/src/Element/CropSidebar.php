<?php
/**
 * @file
 * Contains \Drupal\image_widget_crop\Element\CropSidebar.
 */

namespace Drupal\image_widget_crop\Element;

use Drupal\Core\Render\Element\RenderElement;

/**
 * Provides a container for sidebar crop.
 *
 * @RenderElement("crop_sidebar")
 */
class CropSidebar extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#theme' => 'crop_sidebar',
      '#element_type' => 'ul',
    ];
  }

}
