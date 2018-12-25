<?php

namespace Drupal\file_entity\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Render\Element;

/**
 * Implementation of the 'filesize' formatter for the file_entity files.
 *
 * @FieldFormatter(
 *   id = "file_size",
 *   label = @Translation("File Size"),
 *   field_types = {
 *     "integer"
 *   }
 * )
 */
class FileSizeFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    // This formatter is only available for files.
    return $field_definition->getTargetEntityTypeId() == 'file';
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $files = [$items->getEntity()];

    // Early opt-out if the field is empty.
    if (empty($files)) {
      return $elements;
    }

    foreach ($files as $delta => $file) {
      $elements[$delta] = [
        '#markup' => format_size($file->getSize(), $file->language()->getId()),
      ];
    }

    return $elements;
  }
}
