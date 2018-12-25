<?php
/**
 * @file
 * Contains \Drupal\image_widget_crop\Element\CropHelp.
 */

namespace Drupal\image_widget_crop\Element;

use Drupal\Core\Render\Element\RenderElement;

/**
 * Provides a specific container to help crop element.
 *
 * @RenderElement("crop_help")
 */
class CropHelp extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#theme' => 'crop_help',
      '#text' => ''
    ];
  }

}
