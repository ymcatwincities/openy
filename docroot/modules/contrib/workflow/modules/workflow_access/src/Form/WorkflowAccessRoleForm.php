<?php

/**
 * @file
 * Contains \Drupal\workflow_access\Form\WorkflowAccessRoleForm.
 */

namespace Drupal\workflow_access\Form;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\workflow\Entity\WorkflowState;
use Drupal\workflow_ui\Form\WorkflowConfigTransitionFormBase;

/**
 * Provides the base form for workflow add and edit forms.
 */
class WorkflowAccessRoleForm extends WorkflowConfigTransitionFormBase {

  /**
   * {@inheritdoc}
   */
  protected $entitiesKey = 'workflow_state';

  /**
   * The WorkflowConfigTransition form type.
   *
   * @var string
   */
  protected $type = 'access';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'workflow_access_role';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['workflow_access.role'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = array(
      'label_new' => t('State'),
      'view' => t('Roles who can view posts in this state'),
      'update' => t('Roles who can edit posts in this state'),
      'delete' => t('Roles who can delete posts in this state '),
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
      $state = $entity;
      $sid = $state->id();

      // A list of role names keyed by role ID, including the 'author' role.
      // Only get the roles with proper permission + Author role.
      $type_id = $workflow->id();
      $roles = workflow_get_user_role_names("create $type_id workflow_transition");

      if ($state->isCreationState()) {
        // No need to set perms on creation.
        return [];
      }

      $view = $update = $delete = array();
      $count = 0;
      foreach (workflow_access_get_workflow_access_by_sid($sid) as $rid => $access) {
        $count++;
        $view[$rid] = ($access['grant_view']) ? $rid : 0;
        $update[$rid] = ($access['grant_update']) ? $rid : 0;
        $delete[$rid] = ($access['grant_delete']) ? $rid : 0;
      }
      // Allow view grants by default for anonymous and authenticated users,
      // if no grants were set up earlier.
      if (!$count) {
        $view = array(
          AccountInterface::ANONYMOUS_ROLE => AccountInterface::ANONYMOUS_ROLE,
          AccountInterface::AUTHENTICATED_ROLE =>AccountInterface::AUTHENTICATED_ROLE,
        );
      }

      $row['label_new'] = [
        '#type' => 'value',
        '#markup' => t('@label', array('@label' => $state->label())),
      ];
      $row['view'] = array(
        '#type' => 'checkboxes',
        '#options' => $roles,
        '#default_value' => $view,
      );
      $row['update'] = array(
        '#type' => 'checkboxes',
        '#options' => $roles,
        '#default_value' => $update,
      );
      $row['delete'] = array(
        '#type' => 'checkboxes',
        '#options' => $roles,
        '#default_value' => $delete,
      );
    }
    return $row;
  }

  /**
   * Stores permission settings for workflow states.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getValue($this->entitiesKey) as $sid => $access) {
      // @todo: not waterproof; can be done smarter, using elementchildren()..
      if (!WorkflowState::load($sid)) {
        continue;
      }

      foreach ($access['view'] as $rid => $checked) {
        $data[$rid] = array(
          'grant_view' => (!empty($access['view'][$rid])) ? (bool) $access['view'][$rid] : 0,
          'grant_update' => (!empty($access['update'][$rid])) ? (bool) $access['update'][$rid] : 0,
          'grant_delete' => (!empty($access['delete'][$rid])) ? (bool) $access['delete'][$rid] : 0,
        );
      }
      workflow_access_insert_workflow_access_by_sid($sid, $data);

      // Update all nodes to reflect new settings.
      node_access_needs_rebuild(TRUE);
    }

    drupal_set_message($this->t('The access settings have been saved.'));
  }

}
