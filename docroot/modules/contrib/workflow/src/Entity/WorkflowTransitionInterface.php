<?php

/**
 * @file
 * Contains Drupal\workflow\Entity\WorkflowTransitionInterface.
 */

namespace Drupal\workflow\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Defines a common interface for Workflow*Transition* objects.
 *
 * @see \Drupal\workflow\Entity\WorkflowConfigTransition
 * @see \Drupal\workflow\Entity\WorkflowTransition
 * @see \Drupal\workflow\Entity\WorkflowScheduledTransition
 */
interface WorkflowTransitionInterface extends WorkflowConfigTransitionInterface, EntityInterface, EntityOwnerInterface {

  /**
   * Helper function for __construct. Used for all children of WorkflowTransition (aka WorkflowScheduledTransition)
   *
   * Usage:
   *   $transition = WorkflowTransition::create([$current_sid, 'field_name' => $field_name]);
   *   $transition->setTargetEntity($entity);
   *   $transition->setValues($new_sid, $user->id(), REQUEST_TIME, $comment);
   *
   * @param string $to_sid
   * @param int $uid
   * @param int $timestamp
   * @param string $comment
   */
  public function setValues($to_sid, $uid = NULL, $timestamp = REQUEST_TIME, $comment = '');

  /**
   * Load (Scheduled) WorkflowTransitions, most recent first.
   *
   * @param string $entity_type
   * @param int $entity_id
   * @param array $revision_ids
   * @param string $field_name
   * @param string $langcode
   * @param string $sort
   * @param string $transition_type
   *
   * @return \Drupal\workflow\Entity\WorkflowTransitionInterface object representing one row from the {workflow_transition_history} table.
   * object representing one row from the {workflow_transition_history} table.
   */
  public static function loadByProperties($entity_type, $entity_id, array $revision_ids = [], $field_name = '', $langcode = '', $sort = 'ASC', $transition_type = '');

  /**
   * Given an entity, get all transitions for it.
   *
   * Since this may return a lot of data, a limit is included to allow for only one result.
   *
   * @param string $entity_type
   * @param int[] $entity_ids
   * @param int[] $revision_ids
   * @param string $field_name
   *   Optional. Can be NULL, if you want to load any field.
   * @param string $langcode
   *   Optional. Can be empty, if you want to load any language.
   * @param int $limit
   *   Optional. Can be NULL, if you want to load all transitions.
   * @param string $sort
   *   Optional sort order. {'ASC'|'DESC'}
   * @param string $transition_type
   *   The type trnastion to be fetched.
   *
   * @return WorkflowTransitionInterface[] $transitions
   *   An array of transitions.
   */
  public static function loadMultipleByProperties($entity_type, array $entity_ids, array $revision_ids = [], $field_name = '', $langcode = '',$limit = NULL, $sort = 'ASC', $transition_type = '');

  /**
   * Execute a transition (change state of an entity).
   *
   * A Scheduled Transition shall only be saved, unless the
   * 'schedule' property is set.
   * @usage
   *   $transition->schedule(FALSE);
   *   $to_sid = $transition->execute(TRUE);
   *
   * @param bool $force
   *   If set to TRUE, workflow permissions will be ignored.
   *
   * @return $sid
   *   New state ID. If execution failed, old state ID is returned,
   */
  public function execute($force = FALSE);

  /**
   * Invokes 'transition post'.
   * Adds the possibility to invoke the hook from elsewhere.
   *
   * @param bool $force
   */
  public function post_execute($force = FALSE);

  /**
   * Set the Entity, that is added to the Transition.
   * Also set all dependent fields, that will be saved in tables {workflow_transition_*}
   *
   * @param EntityInterface $entity
   *   The Entity ID or the Entity object, to add to the Transition.
   *
   * @return object $entity
   *   The Entity, that is added to the Transition.
   */
  public function setTargetEntity($entity);

  /**
   * Returns the entity to which the workflow is attached.
   *
   * @return EntityInterface
   *   The entity to which the workflow is attached.
   */
  public function getTargetEntity();

  /**
   * Returns the ID of the entity to which the workflow is attached.
   *
   * @return int
   *   The ID of the entity to which the workflow is attached.
   */
  public function getTargetEntityId();

  /**
   * Returns the type of the entity to which the workflow is attached.
   *
   * @return string
   *   An entity type.
   */
  public function getTargetEntityTypeId();

  /**
   * Get the field_name for which the Transition is valid.
   *
   * @return string $field_name
   *   The field_name, that is added to the Transition.
   */
  public function getFieldName();

  /**
   * Get the language code for which the Transition is valid.
   *
   * @todo: OK?? Shouldn't we use entity's language() method for langcode?
   *
   * @return string $langcode
   */
  public function getLangcode();

  /**
   * Get the comment of the Transition.
   *
   * @return
   *   The comment
   */
  public function getComment();

  /**
   * Get the comment of the Transition.
   *
   * @param $value
   *   The new comment.
   *
   * @return WorkflowTransitionInterface
   */
  public function setComment($value);

  /**
   * Returns the time on which the transitions was or will be executed.
   *
   * @return
   */
  public function getTimestamp();

  /**
   * Returns the human-readable time.
   *
   * @return string
   */
  public function getTimestampFormatted();

  /**
   * Returns the time on which the transitions was or will be executed.
   *
   * @param $value
   *   The new timestamp.
   * @return WorkflowTransitionInterface
   */
  public function setTimestamp($value);

  /**
   * Returns if this is a Scheduled Transition.
   */
  public function isScheduled();
  public function schedule($schedule = TRUE);

  /**
   * Set the 'is_executed' property.
   *
   * @param bool $is_executed
   *
   * @return WorkflowTransitionInterface
   */
  public function setExecuted($is_executed = TRUE);

  /**
   * Returns if this is an Executed Transition.
   *
   * @return bool
   */
  public function isExecuted();

  /**
   * A transition may be forced skipping checks.
   *
   * @return bool
   *  If the transition is forced. (Allow not-configured transitions).
   */
  public function isForced();
  public function force($force = TRUE);

  /**
   * Helper/debugging function. Shows simple contents of Transition.
   *
   * @param string $function
   */
  public function dpm($function = '');
}
