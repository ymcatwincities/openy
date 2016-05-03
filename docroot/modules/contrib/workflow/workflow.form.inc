<?php
/**
 * @file
 * Contains helper functions for WorkflowTransitionForm.
 */

use Drupal\Component\Utility\Html;
use Drupal\workflow\Entity\WorkflowTransitionInterface;

/**
 * Getter/Setter to tell if the action buttons are used.
 *
 * @param bool $new_value
 *   Options. If TRUE/FALSE, the value is set. If NULL, value is only 'getted'.
 *
 * @return bool
 *   Previous value. If TRUE, action buttons must be created.
 *
 * @see workflow_form_alter()
 * @see WorkflowDefaultWidget::formElement()
 *
 * Used to save some expensive operations on every form.
 */
function _workflow_use_action_buttons($new_value = NULL) {
  global $_workflow_use_actions_buttons;

  $old_value = $_workflow_use_actions_buttons ? TRUE : FALSE;
  // Reset value if requested.
  if ($new_value !== NULL) {
    $_workflow_use_actions_buttons = $new_value;
  }
  return $old_value;
}

/**
 * Form builder. Move action buttons next to the 'Save'/'Delete' buttons.
 *
 * This is only used if the set the 'options widget' to 'action buttons'.
 * Do not use with multiple workflows per entity: confusing UX.
 * ATM this works for:
 * - Workflow Field: create, edit, view, workflow tab, comment;
 * - Workflow Node: view, workflow tab;
 * (For forms with Workflow Node, the form_alter() is AFTER formElement(). )
 *
 * @todo: move this to WorkflowTransitionForm::_addActionButtons();
 */
function workflow_form_alter(&$form, \Drupal\Core\Form\FormStateInterface $form_state, $form_id) {
  // N.B. Keep code aligned: workflow_form_alter(), WorkflowTransitionForm::actions().

  // Use a fast, custom way to check if we need to do this.
  // @todo: Make this work with multiple workflows per entity.
  if (!_workflow_use_action_buttons()) {
    return;
  }

  if (isset($form_state->getBuildInfo()['base_form_id']) && $form_state->getBuildInfo()['base_form_id'] == 'workflow_transition_form') {
    // The WorkflowTransitionForm::actions() has its own handling.
    // E.g., Workflow History tab, Block.
    return;
  }

  // Find the first workflow.
  // (So this won't work with multiple workflows per entity.)
  $workflow_element = _workflow_transition_form_get_first_workflow_element($form);

  // Quit if there is no Workflow on this page.
  if (!$workflow_element ) {
    return;
  }

  // Quit if there are no Workflow Action buttons.
  // (If user has only 1 workflow option, there are no Action buttons.)
  if (count($workflow_element['to_sid']['#options']) <= 1) {
    return;
  }

  // Find the default submit button and replace with our own action buttons.
  if (isset($form['actions']['submit'])) {
    $default_submit_action = $form['actions']['submit'];
    // @todo D8-port: on Node-form, this is not sufficient.
    unset($form['actions']['submit']);
  }
  elseif (isset($form['actions']['save'])) {
    $default_submit_action = $form['actions']['save'];
    unset($form['actions']['save']);
  }
  else {
    // totest: when does this happen?
    $default_submit_action = array();
  }
  if (isset($default_submit_action)) {
    $actions = _workflow_transition_form_get_action_buttons($form, $workflow_element, $default_submit_action);
    // Place the button with the other action buttons.
    $form['actions'] += $actions;
  }
}

/**
 * Fetches the first workflow_element from one fo the workflow_fields.
 *
 * @param array $form
 *
 * @return array
 */
function _workflow_transition_form_get_first_workflow_element(&$form) {
  $workflow_element = array();

  // Find the first workflow.
  // (So this won't work with multiple workflows per entity.)
  foreach (\Drupal\Core\Render\Element::children($form) as $key) {
    if (isset($form[$key]['widget'][0]['#default_value'])) {
      $transition = $form[$key]['widget'][0]['#default_value'];
      if (is_object($transition) && $transition instanceof WorkflowTransitionInterface ) {
        $workflow_element = $form[$key]['widget'][0];
        break;
      }
    }
  }
  return $workflow_element;
}

/**
 * Returns the action buttons from the options widget.
 *
 * @param array $form
 * @param array $workflow_element
 * @param array $default_submit_action
 *
 * @return array $actions
 */
function _workflow_transition_form_get_action_buttons(array $form, array $workflow_element, array $default_submit_action) {
  $current_sid = $workflow_element['to_sid']['#default_value'];
  /* @var $transition WorkflowTransitionInterface */
  $transition = $workflow_element['workflow_transition']['#value'];
  $field_name = $transition->getFieldName();

  // Find the default submit button and add our action buttons before it.
  // Get the min weight for our buttons.
  $option_weight = isset($default_submit_action['#weight']) ? $default_submit_action['#weight'] : 0;
  $option_weight = $option_weight - count($workflow_element['to_sid']['#options']);
  $min_weight = $option_weight;
  foreach ($workflow_element['to_sid']['#options'] as $sid => $option_name) {
    // Make the workflow button act exactly like the original submit button.

    $same_state_button = ($sid == $current_sid);
    $workflow_submit_action = $default_submit_action;
    // Add target State ID and Field name, to set correct value in validate_buttons callback.
    $workflow_submit_action['#workflow'] = array(
      'field_name' => $field_name,
      'to_sid' => $sid,
    );
    // Keep option order. Put current state first.
    $workflow_submit_action['#weight'] = ($same_state_button) ? $min_weight : ++$option_weight;
    // Add/Overwrite some other settings.
    $workflow_submit_action['#access'] = TRUE;
    $workflow_submit_action['#value'] = $option_name;
    $workflow_submit_action['#attributes'] = ($same_state_button) ? array('class' => array('form-save-default-button')) : array();
    //$workflow_submit_action['#executes_submit_callback']  = TRUE;
    // Add class to workflow button.
    $workflow_submit_action['#attributes']['class'][] = Html::getClass('workflow_button_' . $option_name);
    // Append the form's #validate function, or it won't be called upon submit,
    // because the workflow buttons have its own #validate.
    $workflow_submit_action['#validate'] = array();
    $workflow_submit_action['#validate'][] = '_workflow_transition_form_validate_buttons';
    if (isset($default_submit_action['#validate'])) {
      $workflow_submit_action['#validate'] = $default_submit_action['#validate'];
    }
    elseif (isset($form['#validate'])) {
      $workflow_submit_action['#validate'] = $form['#validate'];
    }
    // Append the submit-buttons's #submit function, or it won't be called upon submit.
    if (isset($default_submit_action['#submit'])) {
      $workflow_submit_action['#submit'] = $default_submit_action['#submit'];
    }
    elseif (isset($form['#submit'])) {
      $workflow_submit_action['#submit'] = $form['#submit'];
    }

    // Hide the same-state button in some cases.
    if ($same_state_button) {
      if (isset($form['#form_id']) && substr($form['#form_id'], 0, 24) == 'workflow_transition_form') {
        // Hide same-state-button on the transition-form (that is:
        // view page or workflow history tab) if there is nothing to do.
        // However, a Transition may be fieldable.
        if ($form['comment']['#access'] == FALSE) {
          $workflow_submit_action['#access'] = FALSE;
        }
      }
      elseif (isset($form['#id']) && $form['#id'] == 'comment-form') {
        // On comment-form, the button must stay, since you can comment to same state.
      }
      else {
        // On a entity edit page, the button must stay.
      }
    }

    // Place the button with the other action buttons.
    $actions['workflow_' . $sid] = $workflow_submit_action;
  }

  return $actions;
}

/**
 * Get the Workflow parameter from the button, pressed by the user.
 * @param \Drupal\Core\Form\FormStateInterface $form_state
 *  The form state.
 * @return array
 *   A $field_name => $to_sid array.
 */
function _workflow_transition_form_get_triggering_button(\Drupal\Core\Form\FormStateInterface $form_state) {
  $result = ['field_name' =>  '', 'to_sid' =>''];

  $triggering_element = $form_state->getTriggeringElement();
  if (isset($triggering_element['#workflow'])) {
    $result['field_name'] = $triggering_element['#workflow']['field_name'];
    $result['to_sid'] = $triggering_element['#workflow']['to_sid'];
  }

  return $result;
}

/**
 * Submit callback function for the Workflow Form / DefaultWidget.
 *
 * Validate form data for 'time' element.
 */
function _workflow_transition_form_element_validate_time($element, \Drupal\Core\Form\FormStateInterface &$form_state, $form) {
  if (!strtotime($element['#value'])) {
    $form_state->setError($element, t('Please enter a valid value for time.'));
  }
}

/**
 * Submit callback function for the Workflow Form / DefaultWidget.
 *
 * This is only used when using action buttons in the widget.
 * It sets the new state to proper
 * element and sets a submit function if needed, making sure the action is
 * executed, influencing function core/includes/form.inc/form_execute_handlers().
 * (While constructing the Workflow form, we were not yet aware of the submit
 * buttons of the complete form. We try to correct this here, without adding
 * another hook_form_alter. We guess the first button is the Save button.
 */
function _workflow_transition_form_validate_buttons($form, \Drupal\Core\Form\FormStateInterface &$form_state) {
  // Retrieve the data from the form.
  $transition = $form_state->getValue('workflow_transition');
  if ($transition) {
    // On WorkflowTransitionForm :
    // D7: $form_state['input']['to_sid'] = $new_sid;
    // D7: $form_state['values'][$field_name][$langcode][0]['to_sid'] = $new_sid;

    $values = $form_state->getValues();

    $to_sid = $form_state->getTriggeringElement()['#workflow']['to_sid'];
    $values['to_sid'] = $to_sid;

    // Update the form_state.
    $form_state->setValues($values);
  }
  else {
    // On edit form : See $form_state->getTriggeringElement() in WorkflowDefaultWidget;
  }

}
