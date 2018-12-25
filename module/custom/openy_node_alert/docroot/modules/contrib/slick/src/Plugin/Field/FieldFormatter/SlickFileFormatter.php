<?php

namespace Drupal\slick\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\blazy\Dejavu\BlazyVideoTrait;

/**
 * Plugin implementation of the 'Slick File' formatter to get ME within images.
 *
 * This is not 'Slick Media', instead a simple mix of image and optional video.
 */
class SlickFileFormatter extends SlickFileFormatterBase {

  use SlickFormatterTrait;
  use BlazyVideoTrait;

  /**
   * {@inheritdoc}
   */
  public function buildSettings() {
    return ['blazy' => TRUE] + parent::getSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function getScopedFormElements() {
    return [
      'fieldable_form' => TRUE,
      'multimedia'     => TRUE,
      'view_mode'      => $this->viewMode,
    ] + parent::getScopedFormElements();
  }

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    $storage = $field_definition->getFieldStorageDefinition();
    return $storage->isMultiple() && $storage->getSetting('target_type') === 'file';
  }

}
