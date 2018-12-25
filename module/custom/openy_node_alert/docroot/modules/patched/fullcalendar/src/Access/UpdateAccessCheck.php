<?php

namespace Drupal\fullcalendar\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Routing\Access\AccessInterface;

/**
 * @todo.
 */
class UpdateAccessCheck implements AccessInterface {

  /**
   * {@inheritdoc}
   */
  public function access(EntityInterface $entity, AccountInterface $account) {
    return AccessResult::allowedIf($entity && $this->check($entity, $account))->cachePerRole();
  }

  public function check(EntityInterface $entity, AccountInterface $account) {
    return $account->hasPermission('administer content')
        || $account->hasPermission('update any fullcalendar event')
        || $account->hasPermission('edit any ' . $entity->bundle() . ' content')
        || ($account->hasPermission('edit own ' . $entity->bundle() . ' content')
        && $entity->uid == $account->id());
  }

}
