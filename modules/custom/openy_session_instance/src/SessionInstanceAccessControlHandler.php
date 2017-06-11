<?php

namespace Drupal\openy_session_instance;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Session Instance entity.
 *
 * @see \Drupal\openy_session_instance\Entity\SessionInstance.
 */
class SessionInstanceAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\openy_session_instance\Entity\SessionInstanceInterface $entity */
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view session instance entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit session instance entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete session instance entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add session instance entities');
  }

}
