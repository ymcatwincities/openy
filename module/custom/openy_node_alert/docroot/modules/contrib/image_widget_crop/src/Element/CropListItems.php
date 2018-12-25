<?php
/**
 * @file
 * Contains \Drupal\image_widget_crop\Element\CropListItems.
 */

namespace Drupal\image_widget_crop\Element;

use Drupal\Core\Render\Element\RenderElement;

/**
 * Provides a responsive image element.
 *
 * @RenderElement("crop_list_items")
 */
class CropListItems extends RenderElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    return [
      '#theme' => 'crop_list_items',
      '#variables' => [],
    ];
  }

}
