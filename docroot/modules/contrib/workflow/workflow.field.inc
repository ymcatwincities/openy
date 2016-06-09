<?php

/**
 * @file
 * Defines a Workflow field, widget and formatter. (copied from list field).
 */

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\workflow\Entity\Workflow;
use Drupal\workflow\Entity\WorkflowState;

/**
 * Implements hook_form_FORM_ID_alter().
 *
 * Changes the hook_field_settings_form.
 * Fixes some Field settings and Field Instance settings, and makes sure users cannot change it.
 *
 * @todo: perhaps this is core functionality, but these values are only saved
 *        when the site builder explicitly save the instance settings. :-(
 */
function workflow_form_field_storage_config_edit_form_alter(&$form, FormStateInterface $form_state, $form_id) {
  $field_name = $form_state->getFormObject()->getEntity()->getType();

  if ($field_name == 'workflow') {
    // Make sure only 1 value can be entered in the Workflow field.
    $form['cardinality_container']['cardinality']['#default_value'] = 'number';
    $form['cardinality_container']['cardinality']['#disabled'] = TRUE;
    $form['cardinality_container']['cardinality_number']['#default_value'] = 1;
    $form['cardinality_container']['cardinality_number']['#disabled'] = TRUE;
    $form['cardinality_container']['cardinality_number']['#states'] = [];
  }
}

/**
 * Implements hook_form_FORM_ID_alter().
 */
function workflow_form_field_config_edit_form_alter(&$form, FormStateInterface $form_state, $form_id) {
  $field_name = $form_state->getFormObject()->getEntity()->getType();

  if ($field_name == 'workflow') {
    // The Workflow field must have a value, so set to required.
    $form['required']['#default_value'] = 1;
    $form['required']['#disabled'] = TRUE;

    // There are alterations on the widget, too.
    // @see WorkflowDefaultWidget::formElement();
  }
}

/**
 * We will be using some default formatters and widgets from the List and Options modules.
 */

/**
 * Implements hook_field_formatter_info_alter().
 *
 * The module reuses the formatters defined in list.module.
 */
function workflow_field_formatter_info_alter(&$info) {
  $info['list_key']['field_types'][] = 'workflow';
  $info['list_default']['field_types'][] = 'workflow';
}

/**
 * Implements hook_field_widget_info_alter().
 *
 * The module does not implement widgets of its own, but reuses the
 * widgets defined in options.module.
 *
 * @see workflow_options_list()
 */
function workflow_field_widget_info_alter(&$info) {
  $info['options_select']['field_types'][] = 'workflow';
  $info['options_buttons']['field_types'][] = 'workflow';
}

/**
 * {@inheritdoc}
 *
 * @see callback_allowed_values_function()
 *
 * deprecated: workflowfield_options_list --> workflow_state_allowed_values
 */
function workflow_state_allowed_values(FieldStorageDefinitionInterface $definition, FieldableEntityInterface $entity = NULL, &$cacheable = TRUE, AccountInterface $user = NULL) {
//function callback_allowed_values_function(FieldStorageDefinitionInterface $definition, FieldableEntityInterface $entity = NULL, &$cacheable = TRUE) {
// N.B. the parameter $user is added by Workflow module.
  $allowed_options = array();

  $user = workflow_current_user($user); // @todo #2287057: OK?
  $field_name = $definition->getName();

  if ($entity) {
    // Get the allowed new states for the entity's current state.
    // TODO D8-port: overwrite getValue().
    // $sid = $value = $this->getValue();
    $sid = workflow_node_current_state($entity, $field_name);
    $state = WorkflowState::load($sid);
    $allowed_options = ($state) ? $state->getOptions($entity, $field_name, $user, FALSE) : [];
  }
  else {
    // Create an empty State. This triggers to show all possible states for the Workflow.
    $wid = $definition->getSetting('workflow_type');
    if ($workflow = Workflow::load($wid)) {
      // There is no entity, E.g., on the Rules action "Set a data value".
      /* @var $state Drupal\workflow\Entity\WorkflowState */
      $state = WorkflowState::create(['wid' => $wid, 'id' => '']);
      $allowed_options = $state->getOptions(NULL, '', $user, FALSE);
    }
  }

  return $allowed_options;
}


/**
 * Creates a form element to show the current value of a Workflow state.
 *
 * @param EntityInterface $entity
 *   The entity this field is on.
 * @param string $field_name
 *   The field_name
 * @param string $current_sid
 *   The current State Id.
 *
 * @return array
 *   Form element, resembling the formatter of List module.
 *   If state 0 is given, return an empty form element.
 */
function workflow_state_formatter($entity, $field_name, $current_sid = '') {
  $element = array();

  if (!$current_sid) {
    $current_sid = workflow_node_current_state($entity, $field_name);
  }
  // Add the 'current value' formatter for this field.
  // $list_display = $instance['display']['default'];
  $list_display['type'] = 'list_default';
  // Clone the entity and restore old value, in case you want to show an
  // executed transition.
  if ($entity->$field_name->value != $current_sid) {
    $entity = clone $entity;
    $entity->$field_name->value = $current_sid;
  }
  // Generate a renderable array for the field. Use default language determination ($langcode = NULL).
  $element = $entity->$field_name->view($list_display);

  // TODO D8: make weight better (even better: hook_field_extra_fields).
  // Make sure the current value is before the form. (which has weight = 0.005)
  // $element['#weight'] = 0;

  return $element;
}
