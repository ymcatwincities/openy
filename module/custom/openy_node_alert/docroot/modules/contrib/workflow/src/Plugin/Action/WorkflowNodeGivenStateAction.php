<?php

/**
 * @file
 * Contains \Drupal\workflow\Plugin\Action\WorkflowNodeGivenStateAction.
 */

namespace Drupal\workflow\Plugin\Action;

use Drupal\Core\Form\FormStateInterface;

/**
 * Sets an entity to a new, given state.
 *
 * The only change is the 'type' in tha Annotation, so it works on Nodes,
 * and can be seen on admin/content page.
 *
 * @Action(
 *   id = "workflow_node_given_state_action",
 *   label = @Translation("Change a node to new Workflow state"),
 *   type = "node"
 * )
 */
class WorkflowNodeGivenStateAction extends WorkflowStateActionBase {

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
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function execute($object = NULL) {
// D7: As advanced action with Trigger 'node':
// - $entity is empty;
// - $context['group'] = 'node'
// - $context['hook'] = 'node_insert / _update / _delete'
// - $context['node'] = (Object) stdClass
// - $context['entity_type'] = NULL

// D7: As advanced action with Trigger 'taxonomy':
// - $entity is (Object) stdClass;
// - $context['type'] = 'entity'
// - $context['group'] = 'taxonomy'
// - $context['hook'] = 'taxonomy_term_insert / _update / _delete'
// - $context['node'] = (Object) stdClass
// - $context['entity_type'] = NULL

// D7: As advanced action with Trigger 'workflow API':
// ...

// D7: As VBO action:
// - $entity is (Object) stdClass;
// - $context['type'] = NULL
// - $context['group'] = NULL
// - $context['hook'] = NULL
// - $context['node'] = (Object) stdClass
// - $context['entity_type'] = 'node'

    if (!$transition = $this->getTransitionForExecution($object)) {
      drupal_set_message('The entity is not valid for this action.');
      return;
    }

    $force = $this->configuration['force'];
    $transition->force();

    // Fire the transition.
    workflow_execute_transition($transition, $force);
  }

}
