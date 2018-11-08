<?php

namespace Drupal\groupex_form_cache;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the GroupEx Pro Form Cache entity.
 *
 * @see \Drupal\groupex_form_cache\Entity\GroupexFormCache.
 */
class GroupexFormCacheAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\groupex_form_cache\GroupexFormCacheInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished groupex form cache entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published groupex form cache entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit groupex form cache entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete groupex form cache entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add groupex form cache entities');
  }

}
