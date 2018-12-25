<?php

namespace Drupal\personify_mindbody_sync;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Personify MindBody Cache entity.
 *
 * @see \Drupal\personify_mindbody_sync\Entity\PersonifyMindbodyCache.
 */
class PersonifyMindbodyCacheAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\personify_mindbody_sync\PersonifyMindbodyCacheInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished personify mindbody cache entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published personify mindbody cache entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit personify mindbody cache entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete personify mindbody cache entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add personify mindbody cache entities');
  }

}
