<?php

namespace Drupal\openy_media\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Url;
use Drupal\file\Plugin\Field\FieldFormatter\FileFormatterBase;

/**
 * Plugin implementation of the file link field formatter.
 *
 * @FieldFormatter(
 *   id = "openy_file_link",
 *   label = @Translation("File link"),
 *   field_types = {
 *     "file"
 *   }
 * )
 */
class FileLinkFormatter extends FileFormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = array();

    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $file) {
      $image_uri = $file->getFileUri();
      $url = Url::fromUri(file_create_url($image_uri));
      $elements[$delta] = [
        '#title' => $file->getFilename(),
        '#type' => 'link',
        '#url' => $url,
        '#attributes' => ['target' => '_blank'],
        '#cache' => array(
          'tags' => $file->getCacheTags(),
        ),
      ];
    }

    return $elements;
  }

}
