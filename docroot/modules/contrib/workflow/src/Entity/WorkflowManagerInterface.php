<?php

/**
 * @file
 * Contains Drupal\workflow\Entity\WorkflowManagerInterface.
 */

namespace Drupal\workflow\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides an interface for workflow manager.
 *
 * Contains lost of functions from D7 workflow.module file.
 */
interface WorkflowManagerInterface {

  /**
   * Executes a transition (change state of an entity), from OUTSIDE the entity.
   *
   * Use WorkflowManager::executeTransition() to start a State Change from
   *   outside an entity, e.g., workflow_cron().
   * Use $transition->execute() to start a State Change from within an entity.
   *
   * A Scheduled Transition ($transition->isScheduled() == TRUE) will be
   *   un-scheduled and saved in the history table.
   *   The entity will not be updated.
   * If $transition->isScheduled() == FALSE, the Transition will be
   *   removed from the {workflow_transition_scheduled} table (if necessary),
   *   and added to {workflow_transition_history} table.
   *   Then the entity wil be updated to reflect the new status.
   *
   *  If $transition->isForced() == TRUE, transisiton permissions will be
   *    bypassed.
   *
   * @usage
   *   $transition->force($force);
   *   $transition->schedule(FALSE);
   *   $to_sid = Workflow::workflowManager()->executeTransition($transition);
   *
   * @see workflow_execute_transition()
   *
   * @param \Drupal\workflow\Entity\WorkflowTransitionInterface $transition
   *   A WorkflowTransition or WorkflowScheduledTransition.
   * @param bool $force
   *   If set to TRUE, workflow permissions will be ignored.
   *
   * @return string $to_sid
   *   The resulting WorkflowState id.
   */
  public static function executeTransition(WorkflowTransitionInterface $transition, $force);

  /**
   * Given a timeframe, execute all scheduled transitions.
   *
   * Implements hook_cron().
   *
   * @param int $start
   * @param int $end
   */
  public static function executeScheduledTransitionsBetween($start = 0, $end = 0);

  /**
   * Execute a single transition for the given entity.
   *
   * Implements hook_entity insert(), hook_entity_update().
   *
   * When inserting an entity with workflow field, the initial Transition is
   * saved without reference to the proper entity, since Id is not yet known.
   * So, we cannot save Transition in the Widget, but only(?) in a hook.
   * To keep things simple, this is done for both insert() and update().
   *
   * This is referenced in from WorkfowDefaultWidget::massageFormValues().
   *
   * @param \Drupal\workflow\Entity\Drupal\Core\Entity\EntityInterface $entity
   */
  public static function executeTransitionsOfEntity(EntityInterface $entity);

  /********************************************************************
   *
   * Hook-implementing functions.
   *
   */

  /**
   * Implements hook_WORKFLOW_insert().
   *
   * Make sure some roles are allowed to participate in a Workflow by default.
   */
  public static function participateUserRoles(Workflow $workflow);

  /**
   * Implements hook_user_delete().
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   */
  public static function deleteUser(AccountInterface $account);

  /**
   * Implements hook_user_cancel().
   * Implements deprecated workflow_update_workflow_transition_history_uid().
   *
   * " When cancelling the account
   * " - Disable the account and keep its content.
   * " - Disable the account and unpublish its content.
   * " - Delete the account and make its content belong to the Anonymous user.
   * " - Delete the account and its content.
   * "This action cannot be undone.
   *
   * @param $edit
   * @param \Drupal\Core\Session\AccountInterface $account
   * @param string $method
   */
  public static function cancelUser($edit, AccountInterface $account, $method);

  /********************************************************************
   *
   * Helper functions.
   *
   */

  /**
   * Utility function to return an array of workflow fields.
   *
   * @param string $entity_type_id
   *   The content entity type to which the workflow fields are attached.
   *
   * @return array
   *   An array of workflow field map definitions, keyed by field name. Each
   *   value is an array with two entries:
   *   - type: The field type.
   *   - bundles: The bundles in which the field appears, as an array with entity
   *     types as keys and the array of bundle names as values.
   *
   * @see \Drupal\Core\Entity\EntityManagerInterface::getFieldMap()
   */
//  public function getFields($entity_type_id);

  /**
   * Returns the attached fields (via Field UI)
   *
   * @param $entity_type_id
   * @param $bundle
   *
   * @return array
   */
  public function getAttachedFields($entity_type_id, $bundle);

  /**
   * Gets the current state ID of a given entity.
   *
   * There is no need to use a page cache.
   * The performance is OK, and the cache gives problems when using Rules.
   *
   * @param EntityInterface $entity
   *   The entity to check. May be an EntityDrupalWrapper.
   * @param string $field_name
   *   The name of the field of the entity to check.
   *   If empty, the field_name is determined on the spot. This must be avoided,
   *   since it makes having multiple workflow per entity unpredictable.
   *   The found field_name will be returned in the param.
   *
   * @return string $sid
   *   The ID of the current state.
   */
  public static function getCurrentStateId(EntityInterface $entity, $field_name = '');

  /**
   * Gets the previous state ID of a given entity.
   *
   * @param EntityInterface $entity
   * @param string $field_name
   *
   * @return string $sid
   *   The ID of the previous state.
   */
  public static function getPreviousStateId(EntityInterface $entity, $field_name = '');

  /**
   * Determins if User is owner/author of the entity.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *
   * @return bool
   */
  public static function isOwner(AccountInterface $account, EntityInterface $entity = NULL);

  }
