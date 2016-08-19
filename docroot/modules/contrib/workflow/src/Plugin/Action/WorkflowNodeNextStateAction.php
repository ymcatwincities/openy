<?php

/**
 * @file
 * Contains \Drupal\workflow\Plugin\Action\WorkflowNodeNextStateAction.
 */

namespace Drupal\workflow\Plugin\Action;

use Drupal\Core\Form\FormStateInterface;

/**
 * Sets an entity to the next state.
 *
 * The only change is the 'type' in tha Annotation, so it works on Nodes,
 * and can be seen on admin/content page.
 *
 * @Action(
 *   id = "workflow_node_next_state_action",
 *   label = @Translation("Change a node to next Workflow state"),
 *   type = "node"
 * )
 */
class WorkflowNodeNextStateAction extends WorkflowStateActionBase {

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies(){
    return [
      'module' => array('workflow', 'node'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // Remove to_sid. User can't set it, since we want a dynamic 'next' state.
    unset($form['to_sid']);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function execute($object = NULL) {

    if (!$transition = $this->getTransitionForExecution($object)) {
      drupal_set_message('The object is not valid for this action.', 'warning');
      return;
    }

    /*
     * Set the new next state.
     */
    $entity = $transition->getTargetEntity();
    $field_name = $transition->getFieldName();
    $user = $transition->getOwner();
    $force = $this->configuration['force'];
    // $comment = $transition->getComment();

    // Get the node's new State Id (which is the next available state).
    $to_sid = $transition->getWorkflow()->getNextSid($entity, $field_name, $user, $force);

    // Add actual data.
    $transition->to_sid = $to_sid;

    // Fire the transition.
    workflow_execute_transition($transition, $force);
  }

}
