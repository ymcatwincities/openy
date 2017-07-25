<?php

namespace Drupal\address\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Form\FormStateInterface;

/**
 * Represents a list of zone item field values.
 *
 * Works around core not serializing/unserializing default values.
 */
class ZoneItemList extends FieldItemList {

  /**
   * {@inheritdoc}
   */
  public function applyDefaultValue($notify = TRUE) {
    if ($default_value = $this->getFieldDefinition()->getDefaultValue($this->getEntity())) {
      foreach ($default_value as $index => $value) {
        $default_value[$index] = unserialize($value);
      }
      $this->setValue($default_value, $notify);
    }
    else {
      parent::applyDefaultValue($notify);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultValuesFormSubmit(array $element, array &$form, FormStateInterface $form_state) {
    $default_value = parent::defaultValuesFormSubmit($element, $form, $form_state);
    if ($default_value) {
      foreach ($default_value as $index => $value) {
        $default_value[$index] = serialize($value);
      }
    }

    return $default_value;
  }

}
