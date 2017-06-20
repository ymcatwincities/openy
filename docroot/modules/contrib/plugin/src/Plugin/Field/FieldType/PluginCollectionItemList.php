<?php

namespace Drupal\plugin\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a plugin collection field item list.
 */
class PluginCollectionItemList extends FieldItemList {

  /**
   * {@inheritdoc}
   */
  public function defaultValuesFormSubmit(array $element, array &$form, FormStateInterface $form_state) {
    $values = parent::defaultValuesFormSubmit($element, $form, $form_state);
    foreach ($values as $delta => $value) {
      unset($values[$delta]['plugin_instance']);
    }

    return $values;
  }

}
