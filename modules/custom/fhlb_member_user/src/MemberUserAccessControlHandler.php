<?php

namespace Drupal\fhlb_member_user;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Access controller for the Member user entity.
 *
 * @see \Drupal\fhlb_member_user\Entity\MemberUser.
 */
class MemberUserAccessControlHandler extends EntityAccessControlHandler implements EntityHandlerInterface {

  /**
   * The fully loaded current user object.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $entityManager;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeInterface $entity_type, EntityTypeManagerInterface $entity_manager) {
    parent::__construct($entity_type);
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\fhlb_member_user\Entity\MemberUser $entity */

    // Custom Member checkes.
    $roles = $account->getRoles();

    // Member admins rules.
    if (in_array('member_admin', $roles)) {

      // Cannot administer other member admins.
      if ($entity->hasAdminMemberRole()) {
        return AccessResult::forbidden();
      }
      // Must be of the same customer id.
      else {
        $current_user = $this->entityManager->getStorage('user')->load($account->id());
        if ($member = $current_user->field_fhlb_member_user->entity) {
          if ($member->cust_id->value != $entity->cust_id->value) {
            return AccessResult::forbidden();
          }
        }
        // Member admin without member user attached.
        else {
          return AccessResult::forbidden();
        }
      }
    }

    // FHLB Admins cannot administer member users.
    if (in_array('fhlb_admin', $roles) && !$entity->hasAdminMemberRole()) {
      return AccessResult::forbidden();
    }

    // Normal Permissions.
    switch ($operation) {
      case 'view':
        return AccessResult::allowedIfHasPermission($account, 'view member user entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit member user entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete member user entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add member user entities');
  }

}
