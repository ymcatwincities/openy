<?php

/**
 * @file
 * Contains Drupal\workflow\Entity\WorkflowConfigTransitionInterface.
 */

namespace Drupal\workflow\Entity;

use Drupal\user\UserInterface;

/**
 * Defines a common interface for Workflow*Transition* objects.
 *
 * @see \Drupal\workflow\Entity\WorkflowConfigTransition
 * @see \Drupal\workflow\Entity\WorkflowTransition
 * @see \Drupal\workflow\Entity\WorkflowScheduledTransition
 */
interface WorkflowConfigTransitionInterface {

  /**
   * Determines if the current transition between 2 states is allowed:
   * - in settings;
   * - in permissions;
   * - by permission hooks, implemented by other modules.
   *
   * @param \Drupal\user\UserInterface $user
   *   The user to act upon.
   *   May have the custom WORKFLOW_ROLE_AUTHOR_RID role.
   * @param bool $force
   *   Indicates if the transition must be forced(E.g., by cron, rules).
   *
   * @return bool
   *   TRUE if OK, else FALSE.
   */
  public function isAllowed(UserInterface $user, $force = FALSE);

  /**
   * Returns the Workflow object of this State.
   *
   * @return Workflow
   *   Workflow object.
   */
  public function getWorkflow();

  /**
   * Returns the Workflow ID of this Transition
   *
   * @return string
   *   Workflow Id.
   */
  public function getWorkflowId();

  /**
   * @return WorkflowState
   */
  public function getFromState();

  /**
   * @return WorkflowState
   */
  public function getToState();

  /**
   * @return string
   */
  public function getFromSid();

  /**
   * @return string
   */
  public function getToSid();

}
