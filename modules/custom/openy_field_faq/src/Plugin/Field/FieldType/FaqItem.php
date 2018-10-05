<?php

namespace Drupal\openy_field_faq\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'faq' field type.
 *
 * @FieldType(
 *   id = "faq",
 *   label = @Translation("Faq"),
 *   description = @Translation("Faq field."),
 *   default_widget = "faq_default",
 *   default_formatter = "faq_default"
 * )
 */
class FaqItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field) {

    return [
      'columns' => [
        'question' => [
          'type' => 'varchar',
          'length' => 500,
        ],
        'answer' => [
          'type' => 'text',
          'size' => 'big',
        ],
        'format' => [
          'type' => 'varchar_ascii',
          'length' => 255,
        ],
      ],
      'indexes' => [
        'format' => ['format'],
      ],
    ];

  }

  /**
   * {@inheritdoc}
   */
  public function applyDefaultValue($notify = TRUE) {
    $this->setValue(['format' => NULL], $notify);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('question')->getValue() && $this->get('answer')->getValue();
    return $value == NULL;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {

    $properties['question'] = DataDefinition::create('string')
      ->setLabel(t('Question'))
      ->setRequired(TRUE);

    $properties['answer'] = DataDefinition::create('string')
      ->setLabel(t('Answer'))
      ->setRequired(TRUE);

    $properties['format'] = DataDefinition::create('filter_format')
      ->setLabel(t('Text format'));

    $properties['processed'] = DataDefinition::create('string')
      ->setLabel(t('Processed text'))
      ->setDescription(t('The text with the text format applied.'))
      ->setComputed(TRUE)
      ->setClass('\Drupal\text\TextProcessed')
      ->setSetting('text source', 'answer');

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function onChange($property_name, $notify = TRUE) {
    // Unset processed properties that are affected by the change.
    foreach ($this->definition->getPropertyDefinitions() as $property => $definition) {
      if ($definition->getClass() == '\Drupal\text\TextProcessed') {
        if ($property_name == 'format' || ($definition->getSetting('text source') == $property_name)) {
          $this->writePropertyValue($property, NULL);
        }
      }
    }

    parent::onChange($property_name, $notify);
  }

}
