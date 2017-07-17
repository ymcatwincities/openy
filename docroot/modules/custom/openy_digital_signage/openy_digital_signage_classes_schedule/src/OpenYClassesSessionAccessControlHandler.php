<?php

namespace Drupal\openy_digital_signage_classes_schedule;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for Digital Signage Classes Session Item entity.
 *
 * @see \Drupal\openy_digital_signage_classes_schedule\Entity\OpenYClassesSession
 *
 * @ingroup openy_digital_signage_classes_schedule
 */
class OpenYClassesSessionAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /* @var \Drupal\openy_digital_signage_classes_schedule\Entity\OpenYClassesSession $entity */
    switch ($operation) {
      case 'view':
        if ($entity->getSource() != 'manually') {
          return AccessResult::forbidden();
        }
        return AccessResult::allowedIfHasPermission($account, 'view Digital Signage Classes Session entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit Digital Signage Classes Session entities');

      case 'delete':
        if ($entity->isOverridden() && $entity->getSource() != 'manually') {
          return AccessResult::forbidden();
        }
        return AccessResult::allowedIfHasPermission($account, 'delete Digital Signage Classes Session entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add Digital Signage Classes Session entities');
  }

}
