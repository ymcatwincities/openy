<?php

namespace Drupal\blazy\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\blazy\Dejavu\BlazyVideoTrait;

/**
 * Plugin implementation of the 'Blazy File' to get videos within images/files.
 */
class BlazyFileFormatter extends BlazyFileFormatterBase {

  use BlazyFormatterTrait;
  use BlazyVideoTrait;

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
    return $field_definition->getFieldStorageDefinition()->getSetting('target_type') === 'file';
  }

}
