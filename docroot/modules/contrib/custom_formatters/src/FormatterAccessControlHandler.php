<?php

namespace Drupal\custom_formatters;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access control handler for the custom formatter entity type.
 *
 * @see \Drupal\custom_formatters\Entity\Formatter.
 */
class FormatterAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    // Allow all users who can administer the module to do anything.
    if ($account->hasPermission('administer custom formatters')) {
      return AccessResult::allowed();
    }

    return parent::checkAccess($entity, $operation, $account);
  }

}
