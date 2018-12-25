<?php

/**
 * @file
 * Contains \Drupal\config_inspector\Form\ConfigInspectorItemForm.
 */

namespace Drupal\config_inspector\Form;

use Drupal\Core\Config\Schema\ArrayElement;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form for editing configuration translations.
 */
class ConfigInspectorItemForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'config_inspector_item_form';
  }

  /**
   * Build configuration form with metadata and values.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $schema = NULL) {
    $form['structure'] = $this->buildFormConfigElement($schema);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * Format config schema as a tree.
   */
  protected function buildFormConfigElement($schema, $collapsed = FALSE) {
    $build = array();
    foreach ($schema as $key => $element) {
      $definition = $element->getDataDefinition();
      $label = $definition['label'] ?: t('N/A');
      if ($element instanceof ArrayElement) {
        $build[$key] = array(
          '#type' => 'details',
          '#title' => $label,
          '#collapsible' => TRUE,
          '#collapsed' => $collapsed,
        ) + $this->buildFormConfigElement($element, TRUE);
      }
      else {
        $type = $definition['type'];
        switch ($type) {
          case 'boolean':
            $type = 'checkbox';
            break;

          case 'string':
          case 'color_hex':
          case 'path':
          case 'label':
            $type = 'textfield';
            break;

          case 'text':
            $type = 'textarea';
            break;

          case 'integer':
            $type = 'number';
            break;
        }
        $value = $element->getString();
        $build[$key] = array(
          '#type' => $type,
          '#title' => $label,
          '#default_value' => $value,
        );
      }
    }
    return $build;
  }

}
