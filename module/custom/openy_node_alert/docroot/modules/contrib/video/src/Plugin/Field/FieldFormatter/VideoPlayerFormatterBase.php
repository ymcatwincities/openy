<?php

/**
 * @file
 * Contains \Drupal\video\Plugin\Field\FieldFormatter\VidePlayerFormatterBase.
 */

namespace Drupal\video\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\field\FieldConfigInterface;
use Drupal\file\Plugin\Field\FieldFormatter\FileFormatterBase;

/**
 * Base class for video player file formatters.
 */
abstract class VideoPlayerFormatterBase extends FileFormatterBase {

  /**
   * {@inheritdoc}
   */
  protected function getEntitiesToView(EntityReferenceFieldItemListInterface $items, $langcode) {
    return parent::getEntitiesToView($items, $langcode);
  }
}
