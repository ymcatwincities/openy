<?php

namespace Drupal\logger_entity;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Logger Entity entity.
 *
 * @see \Drupal\logger_entity\Entity\LoggerEntity.
 */
class LoggerEntityAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\logger_entity\Entity\LoggerEntityInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished logger entity entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published logger entity entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit logger entity entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete logger entity entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add logger entity entities');
  }

}
