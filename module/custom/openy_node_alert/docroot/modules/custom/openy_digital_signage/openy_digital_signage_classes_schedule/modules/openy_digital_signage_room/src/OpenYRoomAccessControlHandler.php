<?php

namespace Drupal\openy_digital_signage_room;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for Digital Signage Room entity.
 *
 * @see \Drupal\openy_digital_signage_room\Entity\OpenYRoom
 *
 * @ingroup openy_digital_signage_room
 */
class OpenYRoomAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view Digital Signage Room entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit Digital Signage Room entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete Digital Signage Room entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add Digital Signage Room entities');
  }

}
