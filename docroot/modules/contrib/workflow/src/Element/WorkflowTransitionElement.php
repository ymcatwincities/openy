<?php

/**
 * @file
 * Contains Drupal\workflow\Element\WorkflowTransitionElement.
 */

namespace Drupal\workflow\Element;

use Drupal\comment\CommentInterface;
use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Element\FormElement;
use Drupal\workflow\Entity\Workflow;
use Drupal\workflow\Entity\WorkflowScheduledTransition;
use Drupal\workflow\Entity\WorkflowTransitionInterface;

/**
 * Provides a form element for the WorkflowTransitionForm and ~Widget.
 *
 * Properties:
 * - #return_value: The value to return when the checkbox is checked.
 *
 * @see \Drupal\Core\Render\Element\FormElement
 * @see https://www.drupal.org/node/169815 "Creating Custom Elements"
 *
 * @FormElement("workflow_transition")
 */
class WorkflowTransitionElement extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return array(
      '#input' => TRUE,
      '#return_value' => 1,
      '#process' => array(
        array($class, 'processTransition'),
        array($class, 'processAjaxForm'),
//        array($class, 'processGroup'),
      ),
      '#element_validate' => array(
        array($class, 'validateTransition'),
      ),
      '#pre_render' => array(
        array($class, 'preRenderTransition'),
//        array($class, 'preRenderGroup'),
      ),
//      '#theme' => 'input__checkbox',
//      '#theme' => 'input__textfield',
      '#theme_wrappers' => array('form_element'),
//      '#title_display' => 'after',
    );
  }

  /**
   * Form element validation handler.
   *
   * Note that #maxlength is validated by _form_validate() already.
   *
   * This checks that the submitted value:
   * - Does not contain the replacement character only.
   * - Does not contain disallowed characters.
   * - Is unique; i.e., does not already exist.
   * - Does not exceed the maximum length (via #maxlength).
   * - Cannot be changed after creation (via #disabled).
   *
   * @param $element
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param $complete_form
   */
  public static function validateTransition(&$element, FormStateInterface $form_state, &$complete_form) {
    workflow_debug( __FILE__ , __FUNCTION__, __LINE__);  // @todo D8-port: still test this snippet.
  }

  /**
   * Generate an element.
   *
   * This function is referenced in the Annoteation for this class.
   */
  public static function processTransition(&$element, FormStateInterface $form_state, &$complete_form) {
    workflow_debug( __FILE__ , __FUNCTION__, __LINE__);  // @todo D8-port: still test this snippet.
    return self::transitionElement($element, $form_state, $complete_form);
  }

  /**
   * Generate an element.
   *
   * This function is an internal function, to be reused in:
   * - TransitionElement
   * - TransitionDefaultWidget
   *
   * Usage:
   *  @example $element['#default_value'] = $transition;
   *  @example $element += WorkflowTransitionElement::transitionElement($element, $form_state, $form);
   */
  public static function transitionElement(&$element, FormStateInterface $form_state, &$complete_form) {
    // $element = [];

    /*
     * Input.
     */
    // A Transition object must have been set explicitly.
    /* @var $transition WorkflowTransitionInterface */
    $transition = $element['#default_value'];
    /* @var $user \Drupal\Core\Session\AccountInterface */
    $user = \Drupal::currentUser();
    $force = FALSE;

    /*
     * Derived input.
     */
    $field_name = $transition->getFieldName();
    $workflow = $transition->getWorkflow();
    $wid = $transition->getWorkflowId();

    if ($transition->getTargetEntityTypeId() == 'comment') {
      /* @var $comment_entity CommentInterface */
      $comment_entity = $transition->getTargetEntity();
      $entity = ($comment_entity) ? $comment_entity->getCommentedEntity() : NULL;
      $entity_type = ($comment_entity) ? $comment_entity->getCommentedEntityTypeId() : '';
      $entity_id = ($comment_entity) ? $comment_entity->getCommentedEntityId() : '';
      $transition->from_sid = $entity->$field_name->value;
    }
    else {
      $entity = $transition->getTargetEntity();
      $entity_type = $transition->getTargetEntityTypeId();
      $entity_id = $transition->getTargetEntityId();
    }

    if ($transition->isExecuted()) {
      // We are editing an existing/executed/not-scheduled transition.
      // Only the comments may be changed!

      $current_sid = $from_sid = $transition->getFromSid();
      // The states may not be changed anymore.
      $to_state = $transition->getToState();
      $options = array($to_state->id() => $to_state->label());
      // We need the widget to edit the comment.
      $show_widget = TRUE;
      $default_value = $transition->getToSid();
    }
    elseif ($entity) {
      // Normal situation: adding a new transition on an new/existing entity.

      // Get the scheduling info, only when updating an existing entity.
      // This may change the $default_value on the Form.
      // Technically you could have more than one scheduled transition, but
      // this will only add the soonest one.
      // @todo?: Read the history with an explicit langcode.
      $langcode = ''; // $entity->language()->getId();
      if ($entity_id && $scheduled_transition = WorkflowScheduledTransition::loadByProperties($entity_type, $entity_id, [], $field_name, $langcode)) {
        $transition = $scheduled_transition;
      }

      $current_sid = $from_sid = $transition->getFromSid();
      $current_state = $from_state = $transition->getFromState();
      $options = ($current_state) ? $current_state->getOptions($entity, $field_name, $user, $force) : [];
      $show_widget = ($from_state) ? $from_state->showWidget($entity, $field_name, $user, $force) : [];
      $default_value = $from_sid;
      $default_value = ($from_state && $from_state->isCreationState()) ? $workflow->getFirstSid($entity, $field_name, $user, $force) : $default_value;
      $default_value = ($transition->isScheduled()) ? $transition->getToSid() : $default_value;
    }
    elseif (!$entity) {
      // Sometimes, no entity is given. We encountered the following cases:
      // - D7: the Field settings page,
      // - D7: the VBO action form;
      // - D7/D8: the Advance Action form on admin/config/system/actions;
      // If so, show all options for the given workflow(s).
      if(!$temp_state = $transition->getFromState()) {
        $temp_state = $transition->getToState();
      }
      $options = ($temp_state)
        ? $temp_state->getOptions($entity, $field_name, $user, $force)
        : workflow_get_workflow_state_names($wid, $grouped = TRUE, $all = FALSE);
      $show_widget = TRUE;
      $current_sid = $transition->getToSid(); // TODO
      $default_value = $from_sid = $transition->getToSid(); // TODO
    }
    else {
      // We are in trouble! A message is already set in workflow_node_current_state().
      $options = array();
      $show_widget = FALSE;
    }

// Fetch the form ID. This is unique for each entity, to allow multiple form per page (Views, etc.).
// Make it uniquer by adding the field name, or else the scheduling of
// multiple workflow_fields is not independent of eachother.
// IF we are truely on a Transition form (so, not a Node Form with widget)
// then change the form id, too.
    $form_id = 'workflow_transition_form'; // TODO D8-port: add $form_id for widget and History tab.
//    $form_id = $this->getFormId();
    /*
    if (!isset($form_state->getValue('build_info')['base_form_id'])) {
      // Strange: on node form, the base_form_id is node_form,
      // but on term form, it is not set.
      // In both cases, it is OK.
    }
    else {
      workflow_debug( __FILE__ , __FUNCTION__, __LINE__);  // @todo D8-port: still test this snippet.
      if ($form_state['build_info']['base_form_id'] == 'workflow_transition_form') {
        $form_state['build_info']['form_id'] = $form_id;
      }
    }
    */

    /*
     * Output: generate the element.
     */
    // Get settings from workflow. @todo : implement default_settings.
    if ($workflow) {
      $workflow_settings = $workflow->options;
    }
    else {
      // @TODO D8-port: now only tested with Action.
      $workflow_settings = [
        'name_as_title' => 0,
        'options' => "radios",
        'schedule_timezone' => 1,
        'comment_log_node' => "1",
        'comment_log_tab' => "1",
        'watchdog_log' => TRUE,
      ];
    }
    // Current sid and default value may differ in a scheduled transition.
    // Set 'grouped' option. Only valid for select list and undefined/multiple workflows.
    $settings_options_type = $workflow_settings['options'];
    $grouped = ($settings_options_type == 'select');

    $workflow_settings['comment'] = $workflow_settings['comment_log_node']; // vs. ['comment_log_tab'];
// TODO D8-port: remove following code.
//    workflow_debug( __FILE__ , __FUNCTION__, __LINE__);  // @todo D8-port: still test this snippet.
    /*
        // Change settings locally.
        if (!$field_name) {
          // This is a Workflow Node workflow. Set widget options as in v7.x-1.2
          if ($form_state['build_info']['base_form_id'] == 'node_form') {
            $workflow_settings['comment'] = isset($workflow_settings['comment_log_node']) ? $workflow_settings['comment_log_node'] : 1; // vs. ['comment_log_tab'];
            $workflow_settings['current_status'] = TRUE;
          }
          else {
            $workflow_settings['comment'] = isset($workflow_settings['comment_log_tab']) ? $workflow_settings['comment_log_tab'] : 1; // vs. ['comment_log_node'];
            $workflow_settings['current_status'] = TRUE;
          }
        }
    */

    // Capture settings to format the form/widget.
    $settings_title_as_name = !empty($workflow_settings['name_as_title']);
    $settings_fieldset = isset($workflow_settings['fieldset']) ? $workflow_settings['fieldset'] : 0;
    $settings_options_type = $workflow_settings['options'];

    // Display scheduling form if user has permission.
    // Not shown on new entity (not supported by workflow module, because that
    // leaves the entity in the (creation) state until scheduling time.)
    // Not shown when editing existing transition.
    $type_id = ($workflow) ? $workflow->id() : ''; // Might be empty on Action configuration.
    $settings_schedule = !$transition->isExecuted() && $user->hasPermission("schedule $type_id workflow_transition");
    if ($settings_schedule) {
      // TODO D8-port: check below code: form on VBO.
      // workflow_debug( __FILE__ , __FUNCTION__, __LINE__);  // @todo D8-port: still test this snippet.
      $step = $form_state->getValue('step');
      if (isset($step) && ($form_state->getValue('step') == 'views_bulk_operations_config_form')) {
        // On VBO 'modify entity values' form, leave field settings.
        $settings_schedule = TRUE;
      }
      else {
        // ... and cannot be shown on a Content add page (no $entity_id),
        // ...but can be shown on a VBO 'set workflow state to..'page (no entity).
        $settings_schedule = !($entity && !$entity_id);
      }
    }

    $settings_schedule_timezone = !empty($workflow_settings['schedule_timezone']);
    // Show comment, when both Field and Instance allow this.
    $settings_comment = $workflow_settings['comment'];

    $transition_is_scheduled = $transition->isScheduled();
    // Save the current value of the entity in the form, for later Workflow-module specific references.
    // We add prefix, since #tree == FALSE.
    $element['workflow_transition'] = array(
      '#type' => 'value',
      '#value' => $transition,
    );

    // Decide if we show a widget or a formatter.
    // There is no need for a widget when the only option is the current sid.

    // Add a state formatter before the rest of the form,
    // when transition is scheduled or widget is hidden.
    if ( (!$show_widget) || $transition_is_scheduled || $transition->isExecuted()) {
      $element['workflow_current_state'] = workflow_state_formatter($entity, $field_name, $current_sid);
      // Set a proper weight, which works for Workflow Options in select list AND action buttons.
      $element['workflow_current_state']['#weight'] = -0.005;
    }

    $element['#tree'] = TRUE;
    // Add class following node-form pattern (both on form and container).
    $workflow_type_id = ($workflow) ? $workflow->id() : '';
    $element['#attributes']['class'][] = 'workflow-transition-' . $workflow_type_id . '-container';
    $element['#attributes']['class'][] = 'workflow-transition-container';
    if (!$show_widget) {
      // Show no widget.
      $element['to_sid']['#type'] = 'value';
      $element['to_sid']['#value'] = $default_value;
      $element['to_sid']['#options'] = $options; // In case action buttons need them.
      $element['comment']['#type'] = 'value';
      $element['comment']['#value'] = '';

      return $element; // <-- exit.
    }
    else {
      // TODO: repair the usage of $settings_title_as_name: no container if no details (schedule/comment).
      // Prepare a UI wrapper. This might be a fieldset.
      if ($settings_fieldset == 0) { // Use 'container'.
        $element += array(
          '#type' => 'container',
        );
      }
      else {
        $element += array(
          '#type' => 'details',
          '#collapsible' => TRUE,
          '#open' => ($settings_fieldset == 2) ? FALSE : TRUE,
        );
      }

      // This overrides BaseFieldDefinition. @todo: apply for form and widget.
      // The 'options' widget. May be removed later if 'Action buttons' are chosen.
      // The help text is not available for container. Let's add it to the
      // State box. N.B. it is emptyu on Workflow Tab, Node View page.
      $help_text = isset($element['#description']) ? $element['#description'] : '';
      // This overrides BaseFieldDefinition. @todo: apply for form and widget.
      $element['to_sid'] = array(
        '#type' => ($wid) ? $settings_options_type : 'select', // Avoid error with grouped options.
        '#title' => ($settings_title_as_name && !$transition->isExecuted())
           ? t('Change @name state', array('@name' => $workflow->label()))
           : t('Target state'),
        '#access' => TRUE,
        '#options' => $options,
        // '#parents' => array('workflow'),
        '#default_value' => $default_value,
        '#description' => $help_text,
      );
    }

    // Display scheduling form under certain conditions.
    if ($settings_schedule == TRUE) {
      $timezone = drupal_get_user_timezone();

      $timezone_options = array_combine(timezone_identifiers_list(), timezone_identifiers_list());
      $timestamp = $transition ? $transition->getTimestamp() : REQUEST_TIME;
      $hours = (!$transition_is_scheduled) ? '00:00' : format_date($timestamp, 'custom', 'H:i', $timezone);
      // Add a container, so checkbox and time stay together in extra fields.
      $element['workflow_scheduling'] = array(
        '#type' => 'container',
        '#tree' => TRUE,
      );
      $element['workflow_scheduling']['scheduled'] = array(
        '#type' => 'radios',
        '#title' => t('Schedule'),
        '#options' => array(
          '0' => t('Immediately'),
          '1' => t('Schedule for state change'),
        ),
        '#default_value' => $transition_is_scheduled ? '1' : '0',
        '#attributes' => array(
          // 'id' => 'scheduled_' . $form_id,
          'class' => array(Html::getClass('scheduled_' .  $form_id)),
        ),
      );
      $element['workflow_scheduling']['date_time'] = array(
        '#type' => 'details', // 'container',
        '#open' => TRUE, // Controls the HTML5 'open' attribute. Defaults to FALSE.
        '#attributes' => array('class' => array('container-inline')),
        '#prefix' => '<div style="margin-left: 1em;">',
        '#suffix' => '</div>',
        '#states' => array(
          //'visible' => array(':input[id="' . 'scheduled_' . $form_id . '"]' => array('value' => '1')),
          'visible' => array('input.' . Html::getClass('scheduled_' .  $form_id) => array('value' => '1')),
        ),
      );
      $element['workflow_scheduling']['date_time']['workflow_scheduled_date'] = array(
        '#type' => 'date',
        '#prefix' => t('At'),
        '#default_value' => implode( '-', array(
            'year' => date('Y', $timestamp),
            'month' => date('m', $timestamp),
            'day' => date('d', $timestamp),
          )
        )
      );
      $element['workflow_scheduling']['date_time']['workflow_scheduled_hour'] = array(
        '#type' => 'textfield',
        '#title' => t('Time'),
        '#maxlength' => 7,
        '#size' => 6,
        '#default_value' => $hours,
        '#element_validate' => array('_workflow_transition_form_element_validate_time'), // @todo D8-port: this is not called.
      );
      $element['workflow_scheduling']['date_time']['workflow_scheduled_timezone'] = array(
        '#type' => $settings_schedule_timezone ? 'select' : 'hidden',
        '#title' => t('Time zone'),
        '#options' => $timezone_options,
        '#default_value' => array($timezone => $timezone),
      );
      $element['workflow_scheduling']['date_time']['workflow_scheduled_help'] = array(
        '#type' => 'item',
        '#prefix' => '<br />',
        '#description' => t('Please enter a time.
          If no time is included, the default will be midnight on the specified date.
          The current time is: @time.', array('@time' => format_date(REQUEST_TIME, 'custom', 'H:i', $timezone))
        ),
      );
    }

    // This overrides BaseFieldDefinition. @todo: apply for form and widget.
    $element['comment'] = array(
      '#type' => 'textarea',
      '#required' => $settings_comment == '2',
      '#access' => $settings_comment !='0', // Align with action buttons.
      '#title' => t('Workflow comment'),
      '#description' => t('A comment to put in the workflow log.'),
      '#default_value' => $transition ? $transition->getComment() : '',
      '#rows' => 2,
    );

    // TODO D8: make transition fieldable.
    // Add the fields from the WorkflowTransition.
    // field_attach_form('WorkflowTransition', $transition, $element, $form_state);

    // TODO D8-port: test ActionButtons.
    // D7: Finally, add Submit buttons/Action buttons.
    // D8: In WorkflowTransitionForm, a default 'Submit' button is added over there.
    // D8: in Entity Formm, a button per permitted state is added in workflow_form_alter().
    if ($settings_options_type == 'buttons') {
      // D7: How do action buttons work? See also d.o. issue #2187151.
      // D7: Create 'action buttons' per state option. Set $sid property on each button.
      // 1. Admin sets ['widget']['options']['#type'] = 'buttons'.
      // 2. This function formElelent() creates 'action buttons' per state option;
      //    sets $sid property on each button.
      // 3. User clicks button.
      // 4. Callback _workflow_transition_form_validate_buttons() sets proper State.
      // 5. Callback _workflow_transition_form_validate_buttons() sets Submit function.
      // @todo: this does not work yet for the Add Comment form.

      // Performance: inform workflow_form_alter() to do its job.
      _workflow_use_action_buttons(TRUE);

      // Make sure the '#type' is not set to the invalid 'buttons' value.
      // It will be replaced by action buttons, but sometimes, the select box
      // is still shown.
      // @see workflowfield_form_alter().
      $element['to_sid']['#type'] = 'select';
      $element['to_sid']['#access'] = FALSE;
    }
    return $element;
  }

  /**
   * Implements ContentEntityForm::copyFormValuesToEntity(), and is called from:
   * - WorkflowTransitionForm::buildEntity()
   * - WorkflowDefaultWidget
   *
   * N.B. in contrary to ContentEntityForm::copyFormValuesToEntity(),
   * - parameter 1 is returned as result, to be able to create a new Transition object.
   * - parameter 3 is not $form_state (from Form), but an $item array (from Widget).
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param array $form
   * @param array $item
   *
   * @return \Drupal\workflow\Entity\WorkflowTransitionInterface
   */
  static public function copyFormItemValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state, array $item) {
    $user = workflow_current_user(); // @todo #2287057: verify if submit() really is only used for UI. If not, $user must be passed.
    /* @var $transition WorkflowTransitionInterface */
    $transition = $entity;

    /**
     * Derived input
     */
    // Make sure we have subset ['workflow_scheduled_date_time']
    if (isset($item['to_sid'])) {
      // In WorkflowTransitionForm, we receive the complete $form_state.
      // Remember, the workflow_scheduled element is not set on 'add' page.
      $scheduled = !empty($item['workflow_scheduling']['scheduled']);
      $schedule_values = ($scheduled) ? $item['workflow_scheduling']['date_time'] : [];
    }
    else {
      $entity_id = $transition->getTargetEntityId();
      drupal_set_message(t('Error: content @id has no workflow attached. The data is not saved.', array('@id' => $entity_id)), 'error');
      // The new state is still the previous state.
      return $transition;
    }

    // Get user input from element.
    $to_sid = $item['to_sid'];
    $comment = $item['comment'];
    $force = FALSE;

// @todo D8: add the VBO use case.
//    // Determine if the transition is forced.
//    // This can be set by a 'workflow_vbo action' in an additional form element.
//     $force = isset($form_state['input']['workflow_force']) ? $form_state['input']['workflow_force'] : FALSE;
//    if (!$entity) {
//      // E.g., on VBO form.
//    }

    // @todo D8-port: add below exception.
    /*
        // Extract the data from $items, depending on the type of widget.
        // @todo D8: use MassageFormValues($item, $form, $form_state).
        $old_sid = workflow_node_previous_state($entity, $entity_type, $field_name);
        if (!$old_sid) {
          // At this moment, $old_sid should have a value. If the content does not
          // have a state yet, old_sid contains '(creation)' state. But if the
          // content is not associated to a workflow, old_sid is now 0. This may
          // happen in workflow_vbo, if you assign a state to non-relevant nodes.
          $entity_id = entity_id($entity_type, $entity);
          drupal_set_message(t('Error: content @id has no workflow attached. The data is not saved.', array('@id' => $entity_id)), 'error');
          // The new state is still the previous state.
          $new_sid = $old_sid;
          return $new_sid;
        }
    */

    $timestamp = REQUEST_TIME;
    if ($scheduled) {
      // Fetch the (scheduled) timestamp to change the state.
      // Override $timestamp.
      $scheduled_date_time = implode(' ', array(
        $schedule_values['workflow_scheduled_date'],
        $schedule_values['workflow_scheduled_hour'],
        // $schedule_values['workflow_scheduled_timezone'],
      ));
      $timezone = $schedule_values['workflow_scheduled_timezone'];
      $old_timezone = date_default_timezone_get();
      date_default_timezone_set($timezone);
      $timestamp = strtotime($scheduled_date_time);
      date_default_timezone_set($old_timezone);
      if (!$timestamp) {
        // Time should have been validated in form/widget.
        $timestamp = REQUEST_TIME;
      }
    }

    /**
     * Process
     */

    /*
     * Create a new ScheduledTransition.
     */
    if ($scheduled) {
      $transition_entity = $transition->getTargetEntity();
      $field_name = $transition->getFieldName();
      $from_sid = $transition->getFromSid();
      /* @var $transition WorkflowTransitionInterface */
      $transition = WorkflowScheduledTransition::create([$from_sid, 'field_name' => $field_name]);
      $transition->setTargetEntity($transition_entity);
      $transition->setValues($to_sid, $user->id(), $timestamp, $comment);
    }
    if (!$transition->isExecuted()) {
      // Set new values.
      // When editing an existing Transition, only comments may change.
      $transition->set('to_sid', $to_sid);
      $transition->setOwner($user);
      $transition->setTimestamp($timestamp);
      $transition->schedule($scheduled);
      $transition->force($force);
    }
    $transition->setComment($comment);
    // Determine and add the attached fields.
    // Caveat: This works automatically on a Workflow Form,
    // but only with a hack on a widget.
    // It does not support ScheduledTransitions.
    $attached_fields = Workflow::workflowManager()->getAttachedFields('workflow_transition', $transition->bundle());
    foreach ($attached_fields as $attached_field) {
      if (isset($item[$attached_field])) {
        // On Workflow Form. Both lines have the same effect.
        $transition->{$attached_field} = $item[$attached_field];
      }
      else {
        // On Workflow Widget. First line gives empty result.
        //$transition->{$attached_field} = $form_state->value($attached_field);
        $transition->{$attached_field} = $form_state->getUserInput()[$attached_field];
      }
    }

    // Explicitely set $entity in case of ScheduleTransition. It is now returned as parameter, not result.
    $entity = $transition;

    return $transition;
  }

}
