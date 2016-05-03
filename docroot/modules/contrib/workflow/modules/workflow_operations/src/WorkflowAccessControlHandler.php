<?php

/**
 * @file
 * Contains \Drupal\workflow_operations\WorkflowAccessControlHandler.
 */

namespace Drupal\workflow_operations;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\UserInterface;
use Drupal\workflow\Entity\WorkflowManager;
use Drupal\workflow\Entity\WorkflowTransitionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the access control handler for the workflow entity type.
 *
 * @see \Drupal\workflow\Entity\Workflow
 * @ingroup workflow_access
 */
class WorkflowAccessControlHandler extends \Drupal\workflow\WorkflowAccessControlHandler { // EntityAccessControlHandler { // implements EntityHandlerInterface {
//class WorkflowAccessControlHandler extends EntityAccessControlHandler { // implements EntityHandlerInterface {

  /**
   * This is a hack.
   *
   * {@inheritdoc}
   */
  public function __construct(EntityTypeInterface $entity_type = NULL) {
    if ($entity_type) {
      return parent::__construct($entity_type);
    }
    //$this->entityTypeId = $entity_type->id();
    $this->entityType = $entity_type;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type
    );
  }

  /**
   * Access check, to be called from
   * - module.routing.yml
   * - hook_entity_operation
   *
   * @param \Drupal\workflow_operations\WorkflowTransitionInterface|NULL $transition
   *
   * @return \Drupal\Core\Access\AccessResult
   */
  public function revertAccess(WorkflowTransitionInterface $transition = NULL, AccountInterface $account = NULL, $return_as_object = TRUE) {
//public function access(EntityInterface $entity, $operation, AccountInterface $account = NULL, $return_as_object = FALSE) {
    if ($transition) {
      // Called from hook_entity_operation
    }
    else {
      // Called from module.routing.yml
      /* @var $transition WorkflowTransitionInterface */
      $route_match = \Drupal::routeMatch();
      $transition = $route_match->getParameter('workflow_transition');
    }
    return $this->access($transition, 'revert', $account, $return_as_object);
  }

  /**
   * {@inheritdoc}
   */
  public function access(EntityInterface $entity, $operation, AccountInterface $account = NULL, $return_as_object = FALSE) {
    $result = AccessResult::neutral();

    $account = $user = workflow_current_user($account);

    // This is only for Edit/Delete transition. For Add/create, use createAccess.
    switch ($entity->getEntityTypeId()) {

      case 'workflow_transition':
      case 'workflow_scheduled_transition':
        /* @var $transition WorkflowTransitionInterface */
        $transition = $entity;

      switch ($operation) {
        case 'revert':
          $is_owner = WorkflowManager::isOwner($user, $transition);
          $type_id = $transition->getWorkflowId();
          if ($transition->getFromSid() == $transition->getToSid()) {
            // No access for same state transitions.
            $result = AccessResult::forbidden();
          }
          elseif ($user->hasPermission("revert any $type_id workflow_transition")) {
            // OK, add operation.
            $result = AccessResult::allowed();
          }
          elseif ($is_owner && $user->hasPermission("revert own $type_id workflow_transition")) {
            // OK, add operation.
            $result = AccessResult::allowed();
          }
          else {
            // No access.
            $result = AccessResult::forbidden();
          }
          break;

        default:
          $result = parent::access($entity, $operation, $account, $return_as_object)->cachePerPermissions();
          break;
      } // End of switch ($operation).

        break; // case

      default: // $entity_type
        $result = AccessResult::forbidden();
    } // End of  switch($entity->getEntityTypeId()).

    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function createAccess($entity_bundle = NULL, AccountInterface $account = NULL, array $context = array(), $return_as_object = FALSE) {
    workflow_debug( __FILE__ , __FUNCTION__, __LINE__);  // @todo D8-port: still test this snippet.
    $result = parent::createAccess($entity_bundle, $account, $context, TRUE)->cachePerPermissions();
    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $transition, $operation, AccountInterface $account) {
    workflow_debug( __FILE__ , __FUNCTION__, __LINE__);  // @todo D8-port: still test this snippet.
    return parent::checkAccess($transition, $operation, $account);
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    workflow_debug( __FILE__ , __FUNCTION__, __LINE__);  // @todo D8-port: still test this snippet.
    return AccessResult::allowedIf($account->hasPermission('create ' . $entity_bundle . ' content'))->cachePerPermissions();
  }

}
