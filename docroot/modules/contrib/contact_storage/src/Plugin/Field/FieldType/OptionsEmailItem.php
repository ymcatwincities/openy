<?php

namespace Drupal\contact_storage\Plugin\Field\FieldType;

use Drupal\options\Plugin\Field\FieldType\ListItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\Session\AccountInterface;

/**
 * Plugin to add the Option email item custom field type.
 *
 * @FieldType(
 *   id = "contact_storage_options_email",
 *   label = @Translation("Options email"),
 *   description = @Translation("Stores e-mail recipients for the provided options."),
 *   default_widget = "options_select",
 *   default_formatter = "list_default"
 * )
 */
class OptionsEmailItem extends ListItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('Text value'))
      ->addConstraint('Length', array('max' => 255))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'value' => array(
          'type' => 'varchar',
          'length' => 255,
        ),
      ),
      'indexes' => array(
        'value' => array('value'),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function allowedValuesDescription() {
    $description = '<p>' . t('The possible values this field can contain. Enter one value per line, in the format key|label|emails.');
    $description .= '<br/>' . t('"key" is the message that is added to the body of the message.');
    $description .= '<br/>' . t('"label" is the value displayed in the dropdown menu on the contact form.');
    $description .= '<br/>' . t('"emails" are the email addresses to add to the recipients list (each separated by a comma).');
    $description .= '</p>';
    $description .= '<p>' . t('Allowed HTML tags in labels: @tags', array('@tags' => $this->displayAllowedTags())) . '</p>';
    return $description;
  }

  /**
   * {@inheritdoc}
   */
  protected static function extractAllowedValues($string, $has_data) {
    $values = array();

    // Explode the content of the text area per line.
    $list = explode("\n", $string);
    $list = array_map('trim', $list);
    $list = array_filter($list, 'strlen');

    foreach ($list as $text) {
      // Explode each line around the pipe symbol.
      $elements = explode('|', $text);
      // Expects 3 elements (value, label and emails).
      if (count($elements) == 3) {
        // Sanitize the email address.
        if (\Drupal::service('email.validator')->isValid($elements[2])) {
          $values[$elements[0]] = [
            'value' => $elements[1],
            'emails' => $elements[2],
          ];
          continue;
        }
      }
      // Failed at some point. Returns NULL to display an error.
      return;
    }

    if (empty($values)) {
      return;
    }

    return $values;
  }

  /**
   * {@inheritdoc}
   */
  protected static function simplifyAllowedValues(array $structured_values) {
    $values = array();

    foreach ($structured_values as $value) {
      $values[$value['key']] = array(
        'value' => $value['value'],
        'emails' => $value['emails'],
      );
    }
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  protected static function structureAllowedValues(array $values) {
    $structured_values = array();

    foreach ($values as $key => $value) {
      $structured_values[] = array(
        'key' => $key,
        'value' => $value['value'],
        'emails' => $value['emails'],
      );
    }
    return $structured_values;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettableOptions(AccountInterface $account = NULL) {
    $allowed_options = $this->getOptionsAllowedValues();
    // Each option is currently an array containing the value and emails, keyed
    // with the key defined by the user. Remove the array to keep only the key.
    foreach ($allowed_options as $key => $option) {
      $allowed_options_keys[$key] = $key;
    }
    return $allowed_options_keys;
  }

  /**
   * Returns the array of allowed values for the Options email field.
   *
   * @return array
   *   An array of allowed values entered by the user, for the Options email
   *   field.
   */
  protected function getOptionsAllowedValues() {
    return options_allowed_values($this->getFieldDefinition()->getFieldStorageDefinition(), $this->getEntity());
  }

  /**
   * {@inheritdoc}
   */
  protected function allowedValuesString($values) {
    $lines = array();
    foreach ($values as $key => $value) {
      $lines[] = $key . '|' . $value['value'] . '|' . $value['emails'];
    }
    return implode("\n", $lines);
  }

}
