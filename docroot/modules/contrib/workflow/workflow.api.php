<?php
/**
 * @file
 * Hooks provided by the workflow module.
 */

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\UserInterface;
use Drupal\workflow\Entity\Workflow;
use Drupal\workflow\Entity\WorkflowState;
use Drupal\workflow\Entity\WorkflowConfigTransition;
use Drupal\workflow\Entity\WorkflowTransitionInterface;

/**
 * Implements hook_workflow_operations().
 *
 * Adds extra operations to ListBuilders.
 * - workflow_ui: Workflow, State;
 * - workflow: WorkflowTransition;
 *
 * @param string $op
 *   'top_actions': Allow modules to insert their own front page action links.
 *   'operations': Allow modules to insert their own workflow operations.
 *   'state':  Allow modules to insert state operations.
 * @param \Drupal\workflow\Entity\Workflow|NULL $workflow
 *   The current workflow object.
 * @param \Drupal\workflow\Entity\WorkflowState|NULL $state
 *   The current state object.
 * @param \Drupal\workflow\Entity\WorkflowTransitionInterface|NULL $transition
 *   The current transition object.
 *
 * @return array
 *   The new actions, to be added to the entity list.
 */
function hook_workflow_operations($op, EntityInterface $entity = NULL) {
  $operations = array();

  switch ($op) {
    case 'top_actions':
      // As of D8, below hook_workflow_operations is removed, in favour of core hooks.
      // @see file workflow_ui.links.action.yml for an example top action.
      return $operations;

    case 'operations':
      break;

    case 'workflow':
      // This example adds an operation to the 'operations column' of the Workflow List.
      /* @var $workflow Workflow */
      $workflow = $entity;

      $alt = t('Control content access for @wf', array('@wf' => $workflow->label()));
      $attributes = array('alt' => $alt, 'title' => $alt);
      $operations['workflow_access_form'] = array(
        'title' => t('Access'),
        'weight' => 50,
        'url' => \Drupal\Core\Url::fromRoute('entity.workflow_type.access_form', ['workflow_type' => $workflow->id()]),
        'query' => \Drupal::destination()->getAsArray(), // Add destination.
      );
      return $operations;

    case 'state':
      /* @var $state WorkflowState */
      $state = $entity;
      break;

    case 'workflow_transition':
      // As of D8, below hook_workflow_operations is removed, in favour of core hooks.
      // @see EntityListBuilder::getOperations, workflow_operations, workflow.api.php.

      // Your module may add operations to the Entity list.
      /* @var $transition WorkflowTransitionInterface */
      $transition = $entity;
      break;

      return $operations;

    default:
      return $operations;
  }
}

/**
 * Implements hook_workflow().
 *
 * NOTE: This hook may reside in the implementing module
 * or in a module.workflow.inc file.
 *
 * @param string $op
 *   The current workflow operation.
 *   E.g., 'transition pre', 'transition post'.
 * @param mixed $id
 *   The ID of the current state/transition/workflow.
 * @param mixed $new_sid
 *   The state ID of the new state.
 * @param object $entity
 *   The entity whose workflow state is changing.
 * @param bool $force
 *   The caller indicated that the transition should be forced. (bool).
 *   This is only available on the "pre" and "post" calls.
 * @param string $entity_type
 *   The entity_type of the entity whose workflow state is changing.
 * @param string $field_name
 *   The name of the Workflow Field.
 *   This is used when saving a state change of a Workflow Field.
 * @param object $transition
 *   The transition, that contains all of the above.
 *   @todo D8: remove all other parameters.
 *
 * @return mixed
 */
function hook_workflow($op, WorkflowTransitionInterface $transition, UserInterface $user) {
  switch ($op) {
    case 'transition permitted':
      // As of version 8.x-1.x, this operation is never called to check if transition is permitted.
      // This was called in the following situations:
      // case 1. when building a workflow widget with list of available transitions;
      // case 2. when executing a transition, just before the 'transition pre';
      // case 3. when showing a 'revert state' link in a Views display.
      // Your module's implementation may return FALSE here and disallow
      // the execution, or avoid the presentation of the new State.
      // This may be user-dependent.
      // As of version 8.x-1.x:
      // case 1: use hook_workflow_permitted_state_transitions_alter();
      // case 2: use the 'transition pre' operation;
      // case 3: use the 'transition pre' operation;
      return TRUE;

    case 'transition revert':
//      workflow_debug(__FILE__, __FUNCTION__, __LINE__, $op, '');
      // Hook is called when showing the Transition Revert form.
      // Implement this hook if you need to control this.
      // If you return FALSE here, you will veto the transition.
      break;

    case 'transition pre':
//      workflow_debug(__FILE__, __FUNCTION__, __LINE__, $op, '');
      // The workflow module does nothing during this operation.
      // Implement this hook if you need to change/do something BEFORE anything
      // is saved to the database.
      // If you return FALSE here, you will veto the transition.
      break;

    case 'transition post':
//      workflow_debug(__FILE__, __FUNCTION__, __LINE__, $op, '');
      // In D7, this is called by Workflow Node during update of the state, directly
      // after updating the Workflow. Workflow Field does not call this,
      // since you can call a hook_entity_* event after saving the entity.
      // @see https://api.drupal.org/api/drupal/includes%21module.inc/group/hooks/7
      break;

    case 'transition delete':
    case 'state delete':
    case 'workflow delete':
      // These hooks are removed in D8, in favour of the core hooks:
      // - workflow_entity_predelete(EntityInterface $entity)
      // - workflow_entity_delete(EntityInterface $entity)
      // See examples at the bottom of this file.
      break;
  }
}

/**
 * Implements hook_workflow_history_alter().
 *
 * In D8, hook_workflow_history_alter() is removed, in favour
 * of ListBuilder::getDefaultOperations
 * and hook_workflow_operations('workflow_transition').
 *
 * Allow other modules to add Operations to the most recent history change.
 * E.g., Workflow Revert implements an 'undo' operation.
 *
 * @param array $variables
 *   The current workflow history information as an array.
 *   'old_sid' - The state ID of the previous state.
 *   'old_state_name' - The state name of the previous state.
 *   'sid' - The state ID of the current state.
 *   'state_name' - The state name of the current state.
 *   'history' - The row from the workflow_transition_history table.
 *   'transition' - a WorkflowTransition object, containing all of the above.
 */
function hook_workflow_history_alter(array &$variables) {
//  workflow_debug(__FILE__, __FUNCTION__, __LINE__, '', '');

  // The Workflow module does nothing with this hook.
  // For an example implementation, see the Workflow Revert add-on.
}

/**
 * Implements hook_workflow_comment_alter().
 *
 * Allow other modules to change the user comment when saving a state change.
 *
 * @param string $comment
 *   The comment of the current state transition.
 * @param array $context
 *   'transition' - The current transition itself.
 */
function hook_workflow_comment_alter(&$comment, array &$context) {
//  workflow_debug(__FILE__, __FUNCTION__, __LINE__, '', '');

  /* @var $transition WorkflowTransitionInterface */
  $transition = $context['transition'];
  //$comment = $transition->getOwner()->getUsername() . ' says: ' . $comment;
}

/**
 * Implements hook_workflow_permitted_state_transitions_alter().
 *
 * @param array $transitions
 *  An array of allowed transitions from the current state (as provided in
 *  $context). They are already filtered by the settings in Admin UI.
 * @param array $context
 *  An array of relevant objects. Currently:
 *    $context = array(
 *      'user' => $user,
 *      'workflow' => $workflow,
 *      'state' => $current_state,
 *      'force' => $force,
 *    );
 *
 * This hook allows you to add custom filtering of allowed target states, add
 * new custom states, change labels, etc.
 * It is invoked in WorkflowState::getOptions().
 */
function hook_workflow_permitted_state_transitions_alter(array &$transitions, array $context) {
//  workflow_debug(__FILE__, __FUNCTION__, __LINE__, '', '');

  $user = $context['user']; // user may have the custom role AUTHOR.
  // The following could be fetched from each transition.
  $workflow = $context['workflow'];
  $current_state = $context['state'];
  // The following could be fetched from the $user and $transition objects.
  $force = $context['force'];

  // Implement here own permission logic.
  foreach ($transitions as $key => $transition) {
    if (!$transition->isAllowed($user, $force)) {
      //unset($transitions[$key]);
    }
  }

  // This example creates a new custom target state.
  $values = array(
    // Fixed values for new transition.
    'wid' => $context['workflow']->id(),
    'from_sid' => $context['state']->id(),

    // Custom values for new transition.
    // The ID must be an integer, due to db-table constraints.
    'to_sid' => '998',
    'label' => 'go to my new fantasy state',
  );
  $new_transition = WorkflowConfigTransition::create($values);
//  $transitions[] = $new_transition;
}


/**********************************************************************
 * Hooks defined by core Form API: hooks to to alter the Workflow Form/Widget.
 */

/**
 * Alter forms for field widgets provided by other modules.
 *
 * @param $element
 *   The field widget form element as constructed by hook_field_widget_form().
 * @param $form_state
 *   An associative array containing the current state of the form.
 * @param $context
 *   An associative array containing the following key-value pairs, matching the
 *   arguments received by hook_field_widget_form():
 *   - form: The form structure to which widgets are being attached. This may be
 *     a full form structure, or a sub-element of a larger form.
 *   - field: The field structure.
 *   - instance: The field instance structure.
 *   - langcode: The language associated with $items.
 *   - items: Array of default values for this field.
 *   - delta: The order of this item in the array of subelements (0, 1, 2, etc).
 *
 * @see hook_field_widget_form()
 * @see hook_field_widget_WIDGET_TYPE_form_alter()
 */
function hook_field_widget_form_alter(&$element, \Drupal\Core\Form\FormStateInterface $form_state, $context) {
  // A hook for changing any widget. Better not use it: it is called on EVERY
  // Widget. (Even though the message is only shown once.)
  // D7: This hook is introduced in Drupal 7.8.
  // workflow_debug(__FILE__, __FUNCTION__, __LINE__, '', '');
  // dpm($context['widget']->getPluginId());
}

function hook_field_widget_workflow_default_form_alter(&$element, \Drupal\Core\Form\FormStateInterface $form_state, $context) {
  // A hook specific for the 'workflow_default' widget.
  // D7: This hook is introduced in Drupal 7.8.
  // D8: This name is specified in the annotation of WorkflowDefaultWidget.
  workflow_debug(__FILE__, __FUNCTION__, __LINE__, '', '');

  // A widget on an entity form.
  if ('workflow_default' == $context['widget']->getPluginId()) {
    // This object contains all you need. You may find it in one of two locations.
    /* @var $transition WorkflowTransitionInterface */
    /* @var $transition WorkflowTransitionInterface */
    $transition = $element['#default_value'];

    // An example of customizing/overriding the workflow widget.
    // Beware, until now, you must do this twice: on the widget and on the form.
    if ($transition->getOwnerId() == 1) {
      drupal_set_message('I got you, user 1, you will never schedule again,
        and you WILL document each state change!', 'warning');
      // Let's prohibit scheduling for user 1.
      $element['workflow_scheduling']['#access'] = FALSE;
      // Let's prohibit scheduling for user 1.
      if ($element['comment']['#access'] == TRUE) {
        $element['comment']['#required'] = TRUE;
      }
    }
  }
}

/**
 * Implements hook_form_BASE_FORM_ID_alter().
 *
 * Use this hook to alter the form.
 * It is only suited if you only use View Page or Workflow Tab.
 * If you change the state on the Entity Edit page (form), you need the hook
 * hook_form_alter(). See below for more info.
 */
function hook_form_workflow_transition_form_alter(&$form, FormStateInterface $form_state, $form_id) {
//  workflow_debug(__FILE__, __FUNCTION__, __LINE__, $form_id, '');

  // The WorkflowTransitionForm (E.g., Workflow History tab, Block).
  // It has its own handling.
  // Todo, populate the WorkflowTranstiionForm with a Widget, so we have 1 way-of-working.

  // Let's take a (changeable) reference to the element.
  $workflow_element = &$form;

  // This object contains all you need. You may find it in one of two locations.
  /* @var $transition WorkflowTransitionInterface */
  $transition = $form['workflow_transition']['#value'];
  // dpm($transition);

  // An example of customizing/overriding the workflow widget.
  // Beware, until now, you must do this twice: on the widget and on the form.
  if ($transition->getOwnerId() == 1) {
    drupal_set_message('I got you, user 1, you will never schedule again,
        and you WILL document each state change!', 'warning');
    // Let's prohibit scheduling for user 1.
    $workflow_element['workflow_scheduling']['#access'] = FALSE;
    // Let's prohibit scheduling for user 1.
    if ( $workflow_element['comment']['#access'] == TRUE) {
      $workflow_element['comment']['#required'] = TRUE;
    }
  }

  // Get the Entity.
  /* @var $entity \Drupal\Core\Entity\EntityInterface */
  $entity = NULL;
  //$entity = $form['workflow_entity']['#value'];
  $entity_type = 'node'; // $form['workflow_entity_type']['#value'];
  $entity_bundle = ''; // $entity->bundle();
  $sid = '';
  if ($entity) {
    $entity_type = $entity->getEntityTypeId();
    $entity_bundle = $entity->bundle();

    // Get the current State ID.
    $sid = workflow_node_current_state($entity, $field_name = NULL);
    // Get the State object, if needed.
    $state = WorkflowState::load($sid);
  }

  // Change the form, depending on the state ID.
  // In the upcoming version 7.x-2.4, States should have a machine_name, too.
  if ($entity_type == 'node' && $entity_bundle == 'MY_NODE_TYPE') {
    switch ($sid) {
      case '2':
        // Change form element, form validate and form submit for state '2'.
        break;

      case '3':
        // Change form element, form validate and form submit for state '3'.
        break;
    }
  }

}

/**
 * Implements hook_form_alter().
 *
 * Use this hook to alter the form on an Entity Form, Comment Form (Edit page).
 *
 * @see hook_form_workflow_transition_form_alter() for example code.
 */
function hook_form_alter(&$form, FormStateInterface $form_state, $form_id) {
  if (substr($form_id, 0, 8) == 'workflow') {
  //  workflow_debug(__FILE__, __FUNCTION__, __LINE__, $form_id, '');
  }
}


/**********************************************************************
 * Hooks defined by core Entity: hook_entity_CRUD.
 *
 * Instead of using hook_entity_OPERATION, better use hook_ENTITY_TYPE_OPERATION.
 *
 * @see hook_entity_create(), hook_entity_update(), etc.
 * @see hook_ENTITY_TYPE_create(), hook_ENTITY_TYPE_update(), etc.
 */
function hook_entity_predelete(EntityInterface $entity) {
  if (substr($entity->getEntityTypeId(), 0, 8) == 'workflow') {
  //  workflow_debug(__FILE__, __FUNCTION__, __LINE__, 'pre-delete' , $entity->getEntityTypeId());
  }
  switch ($entity->getEntityTypeId()) {
    case 'workflow_config_transition':
    case 'workflow_state':
    case 'workflow_type':
      // Better use hook_ENTITY_TYPE_OPERATION.
      // E.g., hook_workflow_type_predelete
      break;
  }
}

function hook_entity_delete(EntityInterface $entity) {
  if (substr($entity->getEntityTypeId(), 0, 8) == 'workflow') {
  //  workflow_debug(__FILE__, __FUNCTION__, __LINE__, 'delete' , $entity->getEntityTypeId());
  }
}

function hook_workflow_type_delete(EntityInterface $entity) {
//  workflow_debug(__FILE__, __FUNCTION__, __LINE__, 'delete' , $entity->getEntityTypeId());
}

function hook_workflow_config_transition_delete(EntityInterface $entity) {
//  workflow_debug(__FILE__, __FUNCTION__, __LINE__, 'delete' , $entity->getEntityTypeId());
}

function hook_workflow_state_delete(EntityInterface $entity) {
//  workflow_debug(__FILE__, __FUNCTION__, __LINE__, 'delete' , $entity->getEntityTypeId());
}
