<?php
/**
 * @file
 * Contains \Drupal\image_widget_crop\Element\CropContainer.
 */

namespace Drupal\image_widget_crop\Element;

use Drupal\Core\Render\Element\RenderElement;

/**
 * Provides a specific container to ImageCropWidget.
 *
 * @RenderElement("crop_container")
 */
class CropContainer extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#theme' => 'crop_container',
      '#element_type' => 'section'
    ];
  }

}
