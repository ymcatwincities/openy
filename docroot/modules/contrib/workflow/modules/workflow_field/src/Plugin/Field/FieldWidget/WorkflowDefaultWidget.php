<?php

/**
 * @file
 * Contains \Drupal\workflowfield\Plugin\Field\FieldWidget\WorkflowDefaultWidget.
 */

namespace Drupal\workflowfield\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\workflow\Element\WorkflowTransitionElement;
use Drupal\workflow\Entity\Workflow;
use Drupal\workflow\Entity\WorkflowTransition;

/**
 * Plugin implementation of the 'workflow_default' widget.
 *
 * @FieldWidget(
 *   id = "workflow_default",
 *   label = @Translation("Workflow transition form"),
 *   field_types = {
 *     "workflow"
 *   },
 * )
 */
class WorkflowDefaultWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
//      'workflow_default' => array(
//        'label' => t('Workflow'),
//        'field types' => array('workflow'),
//        'settings' => array(
//          'fieldset' => 0,
//          'name_as_title' => 1,
//          'comment' => 1,
//        ),
//      ),
    ) + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $element = array();
    // There are no settings. All is done at Workflow level.
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = array();
    // There are no settings. All is done at Workflow level.
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    workflow_debug(__FILE__, __FUNCTION__, __LINE__);  // @todo D8-port: still test this snippet.

    // Field ID contains entity_type, bundle, field_name.
    $field_id = $this->fieldDefinition->id();
    $entity_id = '';  // TODO D8-port

    $form_id = implode('_', array('workflow_transition_form', $entity_id, $field_id));
    return $form_id;
  }

  /**
   * {@inheritdoc}
   *
   * Be careful: Widget may be shown in very different places. Test carefully!!
   *  - On a entity add/edit page
   *  - On a entity preview page
   *  - On a entity view page
   *  - On a entity 'workflow history' tab
   *  - On a comment display, in the comment history
   *  - On a comment form, below the comment history
   *
   * @todo D8: change "array $items" to "FieldInterface $items"
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {

    $wid = $this->getFieldSetting('workflow_type');
    if (!$workflow = Workflow::load($wid)){
      // @todo: add error message.
      return $element;
    }

    /* @var $item \Drupal\workflowfield\Plugin\Field\FieldType\WorkflowItem */
    $item = $items[$delta];
    /* @var $field_config \Drupal\field\Entity\FieldConfig */
    $field_config = $item->getFieldDefinition();
    /* @var $field_storage \Drupal\field\Entity\FieldStorageConfig */
    $field_storage = $field_config->getFieldStorageDefinition();

    $field_name = $field_storage->get('field_name');
    $entity = $item->getEntity();
    $from_sid = workflow_node_current_state($entity, $field_name);

    // Create a transition, to pass to the form. No need to use setValues().
    /* @var $transition WorkflowTransition */
    $transition = WorkflowTransition::create([$from_sid, 'field_name' => $field_name]);
    $transition->setTargetEntity($entity);

    if (!$this->isDefaultValueWidget($form_state)) {
      // Here, on entity form, not the $element is added, but the entity form.
      // Problem 1: adding the element, does not add added fields.
      // Problem 2: adding the form, generates wrong UI.
      // Problem 3: does not work on ScheduledTransition.

      // Step 1: use the Element.
      $element['#default_value'] = $transition;
      $element += WorkflowTransitionElement::transitionElement($element, $form_state, $form);

      // Step 2: use the Form, in order to get extra fields.
      $entity_form_builder = \Drupal::getContainer()->get('entity.form_builder');
      $workflow_form = $entity_form_builder->getForm($transition, 'add');
      // Determine and add the attached fields.
      $attached_fields = Workflow::workflowManager()->getAttachedFields('workflow_transition', $wid);
      foreach ($attached_fields as $attached_field) {
        $element[$attached_field] = $workflow_form[$attached_field];
      }

      // Option 3: use the true Element.
      // $form = $this->element($form, $form_state, $transition);
      //$element['workflow_transition'] = array(
      //      '#type' => 'workflow_transition',
      //      '#title' => t('Workflow transition'),
      //      '#default_value' => $transition,
      // );

    }
    else {
      // @todo D8: add a default value, so people can set a default comment.
      // On the Field settings page, User may not set a default value
      // (this is done by the Workflow module).
      // @see WorkflowState::getOptions();
      // @see WorkflowDefaultWidget::formElement();
      $element = array();
      return $element;

      workflow_debug( __FILE__, __FUNCTION__, __LINE__, '', '');  // @todo D8-port: still test this snippet.

      // @see workflowfield_form_field_config_edit_form_alter for other settings
      // The Workflow field must have a value, so set to required.
      // Unfortunately, we need hook_form_alter for this.
      //$form['required']['#default_value'] = 1;
      //$form['required']['#disabled'] = TRUE;

      // Explicitly set default value to 'creation' and show element,
      // so people can set a default comment.
      $transition->to_sid = $workflow->getCreationSid();
      $transition->setExecuted(TRUE);

      // @TODO D8-port: use a proper WorkflowTransitionElement call.
      $element['#default_value'] = $transition;
      $element += WorkflowTransitionElement::transitionElement($element, $form_state, $form);

      // No action buttons on default field settings page.
      // This is evaluated in hook_form_alter.
      _workflow_use_action_buttons(FALSE);

      // Make sure the options box is not hidden (when action buttons active).
      //$element['to_sid']['#type'] = 'select';
      $element['to_sid']['#title'] = 'Initial state';
      $element['to_sid']['#access'] = TRUE;

      unset($element['workflow_current_state']);

      return $element;
    };

    return $element;
  }

  /**
   * {@inheritdoc}
   *
   * Implements workflow_transition() -> WorkflowDefaultWidget::submit().
   *
   * Overrides submit(array $form, array &$form_state).
   * Contains 2 extra parameters for D7
   *
   * @param array $form
   * @param array $form_state
   * @param array $items
   *   The value of the field.
   * @param bool $force
   *   TRUE if all access must be overridden, e.g., for Rules.
   *
   * @return int
   *   If update succeeded, the new State Id. Else, the old Id is returned.
   *
   * This is called from function _workflowfield_form_submit($form, &$form_state)
   * It is a replacement of function workflow_transition($entity, $to_sid, $force, $field)
   * It performs the following actions;
   * - save a scheduled action
   * - update history
   * - restore the normal $items for the field.
   * @todo: remove update of {node_form} table. (separate task, because it has features, too)
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {

    $user = workflow_current_user(); // @todo #2287057: verify if submit() really is only used for UI. If not, $user must be passed.

    // Set the new value.
    // Beware: We presume cardinality = 1 !!
    // The widget form element type has transformed the value to a
    // WorkflowTransition object at this point. We need to convert it
    // back to the regular 'value' string format.
    foreach ($values as &$item) {
      if (!empty($item) ) { // } && $item['value'] instanceof DrupalDateTime) {

        // The following can NOT be retrieved from the WorkflowTransition.
        /* @var $entity EntityInterface */
        $entity = $form_state->getFormObject()->getEntity();
        /* @var $transition \Drupal\workflow\Entity\WorkflowTransitionInterface */
        $transition = $item['workflow_transition'];
        $field_name = $transition->getFieldName();
        // N.B. Use a proprietary version of copyFormValuesToEntity,
        // where $entity/$transition is passed by reference.
        // $this->copyFormValuesToEntity($entity, $form, $form_state);
        /* @var $transition \Drupal\workflow\Entity\WorkflowTransitionInterface */
        $transition = WorkflowTransitionElement::copyFormItemValuesToEntity($transition, $form, $form_state, $item);

        // Try to execute the transition. Return $from_sid when error.
        if (!$transition) {
          // This should not be possible (perhaps when testing/developing).
          drupal_set_message(t('Error: the transition from %from_sid to %to_sid could not be generated.'), 'error');
          // The current value is still the previous state.
          $to_sid = $from_sid;
        }
        else {
          // The transition may be scheduled or not. Save the result, and
          // rely upon hook workflow_entity_insert/update($entity) in
          // file workflow.module to save/execute the transition.

          // - validate option; add hook to let other modules change comment.
          // - add to history; add to watchdog
          // Return the new State ID. (Execution may fail and return the old Sid.)

          // Get the new value from an action button if set in the workflow settings.
          $action_info = _workflow_transition_form_get_triggering_button($form_state);
          if ($field_name == $action_info['field_name']) {
            $transition->to_sid->value = $action_info['to_sid'];
          }

          $force = FALSE; // @TODO D8-port: add to form for usage in VBO.

          // Now, save/execute the transition.
          $from_sid = $transition->getFromSid();
          $force = $force || $transition->isForced();

          if (!$transition->isAllowed($user, $force)) {
            // Transition is not allowed.
            $to_sid = $from_sid;
          }
          elseif (!$entity || !$entity->id()) {
            // Entity is inserted. The Id is not yet known.
            // So we can't yet save the transition right now, but must rely on
            // function/hook workflow_entity_insert($entity) in file workflow.module.
            // $to_sid = $transition->execute($force);
            $to_sid = $transition->getToSid();
          }
          else {
            // Entity is updated. To stay in sync with insert, we rely on
            // function/hook workflow_entity_update($entity) in file workflow.module.
            // $to_sid = $transition->execute($force);
            $to_sid = $transition->getToSid();
          }
        }

        // Now the data is captured in the Transition, and before calling the
        // Execution, restore the default values for Workflow Field.
        // For instance, workflow_rules evaluates this.
        //
        // N.B. Align the following functions:
        // - WorkflowDefaultWidget::massageFormValues();
        // - WorkflowManager::executeTransition().
        // Set the transition back, to be used in hook_entity_update().
        $item['workflow_transition'] = $transition;
        // Set the value at the proper location.
        $item['value'] = $to_sid;
      }
    }
    return $values;
  }

}
