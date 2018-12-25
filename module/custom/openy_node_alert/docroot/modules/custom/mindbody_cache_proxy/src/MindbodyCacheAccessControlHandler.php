<?php

namespace Drupal\mindbody_cache_proxy;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the MindBody Cache entity.
 *
 * @see \Drupal\mindbody_cache_proxy\Entity\MindbodyCache.
 */
class MindbodyCacheAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\mindbody_cache_proxy\MindbodyCacheInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished mindbody cache entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published mindbody cache entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit mindbody cache entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete mindbody cache entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add mindbody cache entities');
  }

}
