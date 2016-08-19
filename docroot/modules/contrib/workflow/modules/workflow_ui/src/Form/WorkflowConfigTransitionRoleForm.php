<?php

/**
 * @file
 * Contains \Drupal\workflow_ui\Form\WorkflowConfigTransitionRoleForm.
 */

namespace Drupal\workflow_ui\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\workflow\Entity\WorkflowConfigTransition;

/**
 * Defines a class to build a listing of Workflow Config Transitions entities.
 *
 * @see \Drupal\workflow\Entity\WorkflowConfigTransition
 */
class WorkflowConfigTransitionRoleForm extends WorkflowConfigTransitionFormBase {

  /**
   * {@inheritdoc}
   */
  protected $entitiesKey = 'workflow_state';

  /**
   * {@inheritdoc}
   */
  protected $type = 'permission';

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = array();

    $workflow = $this->workflow;
    $states = $workflow->getStates($all = 'CREATION');
    if ($states) {
      $header['label_new'] = t('From \ To');

      foreach ($states as $state) {
        // Don't allow transition TO (creation).
        if (!$state->isCreationState()) {
          $header[$state->id()] = t('@label', array('@label' => $state->label()));
        }
      }
    }
    return $header;
  }

  /**
   * {@inheritdoc}
   *
   * Builds a row for the following table:
   *   Transitions, for example:
   *     18 => array(
   *       20 => array(
   *         'author' => 1,
   *         1        => 0,
   *         2        => 1,
   *       )
   *     )
   *   means the transition from state 18 to state 20 can be executed by
   *   the content author or a user in role 2. The $transitions array should
   *   contain ALL transitions for the workflow.
   */
  public function buildRow(EntityInterface $entity) {
    $row = array();

    $workflow = $this->workflow;
    if ($workflow) {
      // Each $entity is a from-state.
      /* @var $entity \Drupal\workflow\Entity\WorkflowState */
      $from_state = $entity;
      $from_sid = $from_state->id();

      $states = $workflow->getStates($all = 'CREATION');
      if ($states) {
        // Only get the roles with proper permission + Author role.
        $type_id = $workflow->id();
        $roles = workflow_get_user_role_names("create $type_id workflow_transition");
        // Prepare default value for 'stay_on_this_state'.
        $allow_all_roles = []; // array_combine (array_keys($roles) , array_keys($roles));

        foreach ($states as $state) {
          $row['to'] = [
            '#type' => 'value',
            '#markup' => t('@label', array('@label' => $from_state->label())),
          ];

          foreach ($states as $to_state) {
            // Don't allow transition TO (creation).
            if ($to_state->isCreationState()) {
              continue;
            }
            // Only allow transitions from $from_state.
            if ($state->id() <> $from_state->id()) {
              continue;
            }
            $to_sid = $to_state->id();
            $stay_on_this_state = ($to_sid == $from_sid);

            // Load existing config_transitions. Create if not found.
            $config_transitions = $workflow->getTransitionsByStateId($from_sid, $to_sid);
            if (!$config_transition = reset($config_transitions)) {
              $config_transition = $workflow->createTransition($from_sid, $to_sid);
            }

            $row[$to_sid]['workflow_config_transition'] = ['#type' => 'value', '#value' => $config_transition,];
            $row[$to_sid]['roles'] = [
              '#type' => $stay_on_this_state ? 'checkboxes' : 'checkboxes',
              '#options' => $stay_on_this_state ? [] : $roles,
              '#disabled' => $stay_on_this_state,
              // When $stay_on_this_state, allow all roles.
              '#default_value' => $stay_on_this_state ? $allow_all_roles : $config_transition->roles,
            ];
          }
        }
      }
    }
    return $row;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $workflow = $this->workflow;
    // Make sure 'author' is checked for (creation) -> [something].
    $creation_state = $workflow->getCreationState();

    if (empty($form_state->getValue($this->entitiesKey))) {
      $author_has_permission = TRUE;
    }
    else {
      $author_has_permission = FALSE;
      foreach ($form_state->getValue($this->entitiesKey) as $from_sid => $to_data) {
        foreach ($to_data as $to_sid => $transition_data) {
          if ($from_sid == $to_sid) {
            // Same-state-transition do not count.
          }
          elseif (!empty($transition_data['roles'][WORKFLOW_ROLE_AUTHOR_RID])) {
            $author_has_permission = TRUE;
            break;
          }
        }
      }
    }
    if (!$author_has_permission) {
      $form_state->setErrorByName('id', t('Please give the author permission to go from %creation to at least one state!',
        array('%creation' => $creation_state->label())));
    }

    return;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    foreach ($form_state->getValue($this->entitiesKey) as $from_sid => $to_data) {
      foreach ($to_data as $transition_data) {
        /* @var $config_transition WorkflowConfigTransition */
        if (isset($transition_data['workflow_config_transition'])) {
          $config_transition = $transition_data['workflow_config_transition'];
          $config_transition->roles = $transition_data['roles'];
          $config_transition->save();
        }
        else {
          // Should not be possible.
          // $config_transition = [];
        }
      }
    }

    drupal_set_message(t('The workflow was updated.'));
  }

}
