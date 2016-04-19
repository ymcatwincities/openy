<?php

/**
 * @file
 * Contains \Drupal\workflow_access\Form\WorkflowAccessRoleForm.
 */

namespace Drupal\workflow_access\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\workflow\Entity\WorkflowState;

/**
 * Provides the base form for workflow add and edit forms.
 */
class WorkflowAccessRoleForm extends ConfigFormBase {

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
   * Title callback from workflow_access.routing.yml.
   */
  public function title() {
    // @todo D8: this title and this form are not used.
    $title = 'Access';
    if ($workflow = workflow_ui_url_get_workflow()) {
      $title = t('Access Workflow %name', array('%name' => $workflow->label()));
    }
    return $title;
  }

  /**
   * Implements hook_form().
   *
   * {@inheritdoc}
   *
   * Add a "three dimensional" (state, role, permission type) configuration
   * interface to the workflow edit form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Get the Workflow from the page.
    /* @var $workflow \Drupal\workflow\Entity\Workflow */
    if (!$workflow = workflow_ui_url_get_workflow()) {
      // Leave this page immediately.
      return $form;
    }

    $form = array('#tree' => TRUE);

    $form['#wid'] = $workflow->id();

    // A list of role names keyed by role ID, including the 'author' role.
    // Only get the roles with proper permission + Author role.
    $type_id = $workflow->id();
    $roles = workflow_get_user_role_names("create $type_id workflow_transition");

    // Add a table for every workflow state.
    foreach ($workflow->getStates($all = TRUE) as $sid => $state) {
      if ($state->isCreationState()) {
        // No need to set perms on creation.
        continue;
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

      // @todo: better tables using a #theme function instead of direct #prefixing.
      $form[$sid] = array(
        '#type' => 'fieldset',
        '#title' => $state->label(),
        '#collapsible' => TRUE,
        '#collapsed' => FALSE,
        '#tree' => TRUE,
      );

      $form[$sid]['view'] = array(
        '#type' => 'checkboxes',
        '#options' => $roles,
        '#default_value' => $view,
        '#title' => t('Roles who can view posts in this state'),
        '#prefix' => '<table width="100%" style="border: 0;"><tbody style="border: 0;"><tr><td>',
        '#suffix' => "</td>",
      );

      $form[$sid]['update'] = array(
        '#type' => 'checkboxes',
        '#options' => $roles,
        '#default_value' => $update,
        '#title' => t('Roles who can edit posts in this state'),
        '#prefix' => "<td>",
        '#suffix' => "</td>",
      );

      $form[$sid]['delete'] = array(
        '#type' => 'checkboxes',
        '#options' => $roles,
        '#default_value' => $delete,
        '#title' => t('Roles who can delete posts in this state'),
        '#prefix' => "<td>",
        '#suffix' => "</td></tr></tbody></table>",
      );
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * Form validation handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    return parent::validateForm($form, $form_state);
  }

  /**
   * Stores permission settings for workflow states.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getValues() as $sid => $access) {
      // @todo: not waterproof; can be done smarter, using elementchildren()..
      if (!WorkflowState::load($sid)) {
        continue;
      }

      foreach ($access['view'] as $rid => $checked) {
        $data[$rid] = array(
          'grant_view' => (!empty($checked)) ? (bool) $checked : 0,
          'grant_update' => (!empty($access['update'][$rid])) ? (bool) $access['update'][$rid] : 0,
          'grant_delete' => (!empty($access['delete'][$rid])) ? (bool) $access['delete'][$rid] : 0,
        );
      }
      workflow_access_insert_workflow_access_by_sid($sid, $data);

      // Update all nodes to reflect new settings.
      node_access_needs_rebuild(TRUE);
    }

    parent::submitForm($form, $form_state);
  }

}
