<?php

namespace Drupal\webforms\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\StringItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType;

/**
 * Defines the 'webform_markup_item' field type.
 *
 * @FieldType(
 *   id = "webform_markup_item",
 *   label = @Translation("Markup"),
 *   description = @Translation("A field containing a long string value."),
 *   category = @Translation("Markup"),
 *   default_widget = "webform_markup_widget",
 *   default_formatter = "webform_markup_formatter",
 * )
 */
class MarkupItem extends StringItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'value' => array(
          'type' => $field_definition->getSetting('case_sensitive') ? 'blob' : 'text',
          'size' => 'big',
        ),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $random = new Random();
    $values['value'] = $random->paragraphs();
    return $values;
  }

}
