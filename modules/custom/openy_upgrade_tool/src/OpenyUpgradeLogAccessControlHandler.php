<?php

namespace Drupal\openy_upgrade_tool;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the Openy upgrade log entity.
 *
 * @see \Drupal\openy_upgrade_tool\Entity\OpenyUpgradeLog.
 */
class OpenyUpgradeLogAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\openy_upgrade_tool\Entity\OpenyUpgradeLogInterface $entity */
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view published openy upgrade log entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit openy upgrade log entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete openy upgrade log entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add openy upgrade log entities');
  }

}
