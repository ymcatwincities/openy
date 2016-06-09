<?php

/**
 * @file
 * Contains \Drupal\workflow_operations\Form\WorkflowTransitionRevertForm.
 *
 * @todo: The following annotations should not reside in the Workflow module:
 * at ContentEntityType(
 *   id = "workflow_transition",
 *   handlers = {
 *     "form" = {
 *        "edit" = "Drupal\workflow\Form\WorkflowTransitionForm",
 *        "revert" = "Drupal\workflow_operations\Form\WorkflowTransitionRevertForm",
 *        "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *      }
 *   },
 *   links = {
 *     "edit-form" = "/workflow_transition/{workflow_transition}/edit",
 *     "revert-form" = "/workflow_transition/{workflow_transition}/revert",
 *     "delete-form" = "/workflow_transition/{workflow_transition}/delete",
 *   },
 * )
 *
 */

namespace Drupal\workflow_operations\Form;

use Drupal\Core\Entity\EntityConfirmFormBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\workflow\Entity\WorkflowTransition;
use Drupal\workflow\Entity\WorkflowTransitionInterface;

class WorkflowTransitionRevertForm extends EntityConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'workflow_transition_revert_confirm';
  }

  public function getQuestion() {
    /* @var $transition WorkflowTransitionInterface */
    $transition = $this->entity;
    $state = $transition->getFromState();

    if ($state) {
      $question = t('Are you sure you want to revert %title to the "@state" state?', [
        '@state' => $state->label(),
        '%title' => $transition->label(),
      ]);
      return $question;
    }
    else {
      \Drupal::logger('workflow_revert')->error('Invalid state', []);
      drupal_set_message(t('Invalid transition. Your information has been recorded.'), 'error');
//      drupal_goto($return_uri);
    }
  }

  public function getCancelUrl() {
    /* @var $transition WorkflowTransitionInterface */
    $transition = $this->entity;
    return new Url('entity.node.workflow_history', array('node' => $transition->getTargetEntityId(), 'field_name' => $transition->getFieldname()));
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return t('Revert');
  }

  /**
   * {@inheritdoc}
   */
//  public function getDescription() {
//    return '';
//  }

  /**
   * The fact that we need to overwrite this function, is an indicator that
   * the Transition is not completely a complete Entity.
   *
   * {@inheritdoc}
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    //return parent::copyFormValuesToEntity($entity, $form, $form_state);
    return $this->entity;
  }

  public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {

    // If Rules is available, signal the reversion.
    // @todo: move this Rules_invoke_event to hook outside this module.
    if(\Drupal::moduleHandler()->moduleExists('rules')) {
      rules_invoke_event('workflow_state_reverted', $this->entity);
    }

    /* @var $transition WorkflowTransitionInterface */
    $transition = $this->prepareRevertedTransition($this->entity);

    // The entity will be updated when the transition is executed. Keep the
    // original one for the confirmation message.
    $previous_sid = $transition->getToSid();

    // Force the transition because it's probably not valid.
    $transition->force(TRUE);
    $new_sid = workflow_execute_transition($transition, TRUE);

    $comment = ($previous_sid == $new_sid) ? 'State is reverted.' : 'State could not be reverted.';
    drupal_set_message(t($comment), 'warning');

    $form_state->setRedirect('entity.node.workflow_history', array(
        'node' => $transition->getTargetEntityId(),
        'field_name' => $transition->getFieldName(),
      )
    );
  }

  /**
   * Prepares a transition to be reverted.
   *
   * @param \Drupal\workflow\Entity\WorkflowTransitionInterface $transition
   *   The transition to be reverted.
   *
   * @return \Drupal\workflow\Entity\WorkflowTransitionInterface
   *   The prepared transition ready to be stored.
   */
  protected function prepareRevertedTransition(WorkflowTransitionInterface $transition) {
    $user = \Drupal::currentUser();

    $entity = $transition->getTargetEntity();
    $field_name = $transition->getFieldName();
    $current_sid = workflow_node_current_state($entity, $field_name);
    $previous_sid = $transition->getFromSid();
    $comment = t('State reverted.');

    $transition = WorkflowTransition::create([$current_sid, 'field_name' => $field_name]);
    $transition->setTargetEntity($entity);
    $transition->setValues($previous_sid, $user->id(), REQUEST_TIME, $comment);

    return $transition;
  }

}
