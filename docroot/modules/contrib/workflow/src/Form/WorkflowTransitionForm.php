<?php

/**
 * @file
 * Contains \Drupal\workflow\Form\WorkflowTransitionForm.
 */

namespace Drupal\workflow\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\workflow\Element\WorkflowTransitionElement;
use Drupal\workflow\Entity\Workflow;
use Drupal\workflow\Entity\WorkflowTransitionInterface;

/**
 * Provides a Transition Form to be used in the Workflow Widget.
 */
class WorkflowTransitionForm extends ContentEntityForm {

  /*************************************************************************
   *
   * Implementation of interface FormInterface.
   *
   */

  /**
   * {@inheritdoc}
   */
  public function getFormId() {

    /* @var $transition WorkflowTransitionInterface */
    $transition = $this->entity;
    $field_name = $transition->getFieldName();

    /* @var $entity EntityInterface */
    // Entity may be empty on VBO bulk form.
    // $entity = $transition->getTargetEntity();
    // Compose Form Id from string + Entity Id + Field name.
    // Field ID contains entity_type, bundle, field_name.
    // The Form Id is unique, to allow for multiple forms per page.
    // $workflow_type_id = $transition->getWorkflowId();
    // Field name contains implicit entity_type & bundle (since 1 field per entity)
    // $entity_type = $transition->getTargetEntityTypeId();
    // $entity_id = $transition->getTargetEntityId();;

    // Emulate nodeForm convention.
    if ($transition->id()) {
      $suffix = 'edit_form';
    }
    else {
      $suffix = 'form';
    }
    $form_id = implode('_', array('workflow_transition', $field_name, $suffix));
    $form_id = Html::getUniqueId($form_id);

    return $form_id;
  }

  /**
   * {@inheritdoc}
   *
   * N.B. The D8-version of this form is stripped. If any use case is missing:
   * - compare with the D7-version of WorkflowTransitionForm::submitForm()
   * - compare with the D8-version of WorkflowTransitionElement::copyFormValuesToEntity()
   */
//  public function submitForm(array &$form, FormStateInterface $form_state) {
//    parent::submitForm($form, $form_state);
//  }

  /*************************************************************************
   *
   * Implementation of interface EntityFormInterface (extends FormInterface).
   *
   */

  /**
   * This function is called by buildForm().
   * Caveat: !! It is not declared in the EntityFormInterface !!
   *
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    // Call parent to get (extra) fields.
    // This might cause baseFieldDefinitions to appear twice.
    $form = parent::form($form, $form_state);

    /* @var $transition WorkflowTransitionInterface */
    $transition = $this->entity;

    // Do not pass the element, but the form.
    // $element['#default_value'] = $transition;
    // $form += WorkflowTransitionElement::transitionElement($element, $form_state, $form);
    //
    // Pass the form via parameter
    $form['#default_value'] = $transition;
    $form = WorkflowTransitionElement::transitionElement($form, $form_state, $form);
    return $form;
  }

  /**
   * Returns the action form element for the current entity form.
   * Caveat: !! It is not declared in the EntityFormInterface !!
   *
   * {@inheritdoc}
   */
//  protected function actionsElement(array $form, FormStateInterface $form_state) {
//    $element = parent::actionsElement($form, $form_state);
//    return $element;
//  }

  /**
   * Returns an array of supported actions for the current entity form.
   * Caveat: !! It is not declared in the EntityFormInterface !!
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @return
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    // N.B. Keep code aligned: workflow_form_alter(), WorkflowTransitionForm::actions().
    $actions = parent::actions($form, $form_state);

    // A default button is provided by core. Override it.
    $actions['submit']['#value'] = t('Update workflow');
    $actions['submit']['#attributes'] = array('class' => array('form-save-default-button'));

    if (!_workflow_use_action_buttons()) {
      // Change the default submit button on the Workflow History tab.
      return $actions;
    }
    else {
      // Action buttons are activated.

      // Find the first workflow.
      // (So this won't work with multiple workflows per entity.)
      $workflow_form = &$form;

      // Quit if there is no Workflow on this page.
      if (!$workflow_form ) {
        return;
      }

      // Quit if there are no Workflow Action buttons.
      // (If user has only 1 workflow option, there are no Action buttons.)
      if (count($workflow_form['to_sid']['#options']) <= 1) {
        return;
      }

      // Place the buttons. Remove the default 'Save' button.
      // $actions += _workflow_transition_form_get_action_buttons($form, $workflow_form);
      // Remove the default submit button from the form.
      // unset($actions['submit']);
      $default_submit_action = $actions['submit'];
      $actions = _workflow_transition_form_get_action_buttons($form, $workflow_form, $default_submit_action);
      foreach ($actions as $key => &$action) {
        $action['#submit'] = $default_submit_action['#submit'];
      }
    }

    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    $entity = clone $this->entity;
    // N.B. Use a proprietary version of copyFormValuesToEntity,
    // where $entity is passed by reference.
    // $this->copyFormValuesToEntity($entity, $form, $form_state);
    $item = $form_state->getValues();
    $entity = WorkflowTransitionElement::copyFormItemValuesToEntity($entity, $form, $form_state, $item);

    // Mark the entity as NOT requiring validation. (Used in validateForm().)
    $entity->setValidationRequired(FALSE);

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
//  public function buildForm(array $form, FormStateInterface $form_state) {
//    $form = parent::buildForm($form, $form_state);
//
//    // Add class following node-form pattern (both on form and container).
//    // D8-port: This is apparently already magically set in parent.
//    // $form['#attributes']['class'][] = 'workflow-transition-' . $workflow_type_id . '-form';
//    // $form['#attributes']['class'][] = 'workflow-transition-form';
//    return $form;
//  }

  /**
   * {@inheritdoc}
   *
   * This is called from submitForm().
   */
  public function save(array $form, FormStateInterface $form_state) {
    // Execute transition and update the attached entity.
    $entity = $this->getEntity();
    return Workflow::workflowManager()->executeTransition($entity);
  }

  /*************************************************************************
   *
   * Implementation of interface ContentEntityFormInterface (extends EntityFormInterface).
   *
   */

  /**
   * {@inheritdoc}
   */
//  public function validateForm(array &$form, FormStateInterface $form_state) {
//    return parent::validateForm($form, $form_state);
//  }

}
