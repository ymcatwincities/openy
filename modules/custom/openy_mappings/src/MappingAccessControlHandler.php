<?php

namespace Drupal\openy_mappings;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Mapping entity.
 *
 * @see \Drupal\openy_mappings\Entity\Mapping.
 */
class MappingAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\openy_mappings\MappingInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished mapping entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published mapping entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit mapping entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete mapping entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add mapping entities');
  }

}
