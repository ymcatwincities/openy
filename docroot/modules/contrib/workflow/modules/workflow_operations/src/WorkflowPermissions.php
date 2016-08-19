<?php

/**
 * @file
 * Contains \Drupal\workflow_operations\WorkflowPermissions.
 */

namespace Drupal\workflow_operations;

/**
 * Provides dynamic permissions for workflows of different types.
 */
class WorkflowPermissions extends \Drupal\workflow\WorkflowPermissions {

  /**
   * Returns an array of workflow type permissions.
   *
   * @return array
   *   The workflow type permissions.
   *   @see \Drupal\user\PermissionHandlerInterface::getPermissions()
   */
//  public function workflowTypePermissions() {
//    return parent::workflowTypePermissions();
//  }

  /**
   * Returns a list of workflow permissions for a given workflow type.
   *
   * @param \Drupal\workflow\Entity\WorkflowType $type
   *   The workflow type.
   *
   * @return array
   *   An associative array of permission names and descriptions.
   */
  protected function buildPermissions(\Drupal\workflow\Entity\Workflow $type) {
    $type_id = $type->id();
    $type_params = array('%type_name' => $type->label());

    return array(
      // D7->D8-Conversion of 'edit workflow comment' permission to "edit own $type_id transition" (@see NodePermissions::edit any/own content).
      // D7->D8-Conversion of 'edit workflow comment' permission to "edit any $type_id transition" (@see NodePermissions::edit any/own content).
      "edit own $type_id workflow_transition" => array(
        'title' => $this->t('%type_name: Edit own comments', $type_params),
        'description' => t('Edit the comment of own executed state transitions.'),
        'restrict access' => TRUE,
      ),
      "edit any $type_id workflow_transition" => array(
        'title' => $this->t('%type_name: Edit any comments', $type_params),
        'description' => t('Edit the comment of any executed state transitions.'),
        'restrict access' => TRUE,
      ),
      // Workflow has no 'delete' permissions.
//      "delete own $type_id workflow_transition" => array(
//        'title' => $this->t('%type_name: Delete own content', $type_params),
//      ),
//      "delete any $type_id workflow_transition" => array(
//        'title' => $this->t('%type_name: Delete any content', $type_params),
//      ),
      // D7->D8-Conversion of 'revert workflow' permission to "revert any/own $type_id transition"
      "revert own $type_id workflow_transition" => array(
        'title' => $this->t('%type_name: Revert own state transition', $type_params),
        'description' => t('Allow user to revert own last executed state transition on entity.'),
        'restrict access' => TRUE,
      ),
      "revert any $type_id workflow_transition" => array(
        'title' => $this->t('%type_name: Revert any state transition', $type_params),
        'description' => t('Allow user to revert any last executed state transition on entity.'),
        'restrict access' => TRUE,
      ),
    );
  }

}
