<?php

namespace Drupal\openy_digital_signage_screen;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the OpenY Digital Signage Screen entity.
 *
 * @see \Drupal\openy_digital_signage_screen\Entity\OpenYScreen
 */
class OpenYScreenAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\openy_digital_signage_screen\Entity\OpenYScreenInterface $entity */
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view OpenY Digital Signage Screen entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit OpenY Digital Signage Screen entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete OpenY Digital Signage Screen entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add OpenY Digital Signage Screen entities');
  }

}
