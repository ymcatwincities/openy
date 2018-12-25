<?php
/**
 * @file
 * Contains \Drupal\image_widget_crop\Element\CropImageContainer.
 */

namespace Drupal\image_widget_crop\Element;

use Drupal\Core\Render\Element\RenderElement;

/**
 * Provides list to render sidebar crop items.
 *
 * @RenderElement("crop_image_container")
 */
class CropImageContainer extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#theme' => 'crop_image_container',
    ];
  }

}
