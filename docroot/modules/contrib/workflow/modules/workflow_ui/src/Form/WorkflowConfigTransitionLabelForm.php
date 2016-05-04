<?php

/**
 * @file
 * Contains \Drupal\workflow_ui\Form\WorkflowConfigTransitionLabelForm.
 */

namespace Drupal\workflow_ui\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a class to build a draggable listing of Workflow Config Transitions entities.
 *
 * @see \Drupal\workflow\Entity\WorkflowConfigTransition
 */
//class WorkflowConfigTransitionLabelListBuilder extends ConfigEntityListBuilder implements FormInterface {
class WorkflowConfigTransitionLabelForm extends WorkflowConfigTransitionFormBase {

  /**
   * {@inheritdoc}
   */
  protected $entitiesKey = 'workflow_config_transition';

  /**
   * {@inheritdoc}
   */
  protected $type = 'label';

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = array(
      'from' => t('Transition from'),
      'to' => t('Transition to'),
      'label_new' => t('label'),
      'config_transition' => '',
    );

    return $header;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $row = array();

    $workflow = $this->workflow;
    if ($workflow) {
      /* @var $entity \Drupal\workflow\Entity\WorkflowConfigTransition */
      $config_transition = $entity;

      static $previous_from_sid = -1;
      // Get transitions, sorted by weight of the old state.
      $from_state = $config_transition->getFromState();
      $to_state = $config_transition->getToState();
      $from_sid = $from_state->id();

      $row['from'] = [
        '#type' => 'value',
        '#markup' => ($previous_from_sid != $from_sid) ? $from_state->label() : '"',
      ];
      $row['to'] = [
        '#type' => 'value',
        '#markup' => $to_state->label(),
      ];
      $row['label_new'] = [
        '#type' => 'textfield',
        '#default_value' => $config_transition->get('label'),
      ];
      $row['config_transition'] = [
        '#type' => 'value',
        '#value' => $config_transition,
      ];

      $previous_from_sid = $from_sid;
    }
    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getValue($this->entitiesKey) as $key => $value) {
      $new_label = trim($value['label_new']);
      $value['config_transition']
        ->set('label', $new_label)
        ->save();
    }

    drupal_set_message(t('The transition labels have been saved.'));
  }

}
