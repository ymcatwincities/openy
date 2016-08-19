<?php

namespace Drupal\ymca_groupex_google_cache;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Groupex Google Cache entity.
 *
 * @see \Drupal\ymca_groupex_google_cache\Entity\GroupexGoogleCache.
 */
class GroupexGoogleCacheAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\ymca_groupex_google_cache\GroupexGoogleCacheInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished groupex google cache entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published groupex google cache entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit groupex google cache entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete groupex google cache entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add groupex google cache entities');
  }

}
