<?php

namespace Drupal\file_browser\Plugin\views\field;

use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Defines a custom field that renders a preview of a file, for the purposes of.
 *
 * @ViewsField("file_browser_preview")
 */
class FileBrowserPreview extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    /** @var \Drupal\file\Entity\File $file */
    $file = $this->getEntity($values);
    $build = [];

    // Check if this file is an image.
    $image_factory = \Drupal::service('image.factory');

    // Loading large files is slow, make sure it is an image mime type before
    // doing that.
    list($type,) = explode('/', $file->getMimeType(), 2);
    if ($type == 'image' && ($image = $image_factory->get($file->getFileUri())) && $image->isValid()) {
      // Fake an ImageItem object.
      $item = new \stdClass();
      $item->width = $image->getWidth();
      $item->height = $image->getHeight();
      $item->alt = '';
      $item->title = $file->getFilename();
      $item->entity = $file;

      // Render the original image, Masonry takes care of scaling.
      $build = [
        '#theme' => 'image_formatter',
        '#item' => $item,
        '#image_style' => 'file_entity_browser_thumbnail',
      ];
    }
    // Use a placeholder image for now.
    // @todo See if we can use fallback formatters for this.
    else {
      $path = drupal_get_path('module', 'file_browser');
      $build = [
        '#theme' => 'image',
        '#attributes' => array(
          'src' => base_path() . $path . '/images/document_placeholder.svg',
        ),
      ];
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {}

  /**
   * {@inheritdoc}
   */
  public function clickSortable() {
    return FALSE;
  }

}
