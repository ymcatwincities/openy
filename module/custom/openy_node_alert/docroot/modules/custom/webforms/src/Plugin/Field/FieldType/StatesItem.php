<?php

namespace Drupal\webforms\Plugin\Field\FieldType;

use Drupal\Component\Utility\Random;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\StringItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType;

/**
 * Defines the 'webform_states' field type.
 *
 * @FieldType(
 *   id = "webform_states_item",
 *   label = @Translation("List of US and Canada states and provinces"),
 *   description = @Translation("A field containing list of US and Canada states and provinces."),
 *   category = @Translation("List"),
 *   default_widget = "webform_states_widget",
 *   default_formatter = "webform_states_formatter",
 * )
 */
class StatesItem extends StringItemBase {

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
