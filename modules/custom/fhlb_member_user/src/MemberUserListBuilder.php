<?php

namespace Drupal\fhlb_member_user;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\fhlb_user_roles\FhlbUserRoles;

/**
 * Defines a class to build a listing of Member user entities.
 *
 * @ingroup fhlb_member_user
 */
class MemberUserListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['cust_id'] = $this->t('Customer ID');
    $header['first_name'] = $this->t('First Name');
    $header['last_name'] = $this->t('Last Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\fhlb_member_user\Entity\MemberUser */
    $row['cust_id'] = $entity->cust_id->value;
    $row['first_name'] = $entity->first_name->value;
    $row['last_name'] = $entity->last_name->value;
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityIds() {
    $current_user_roles = \Drupal::currentUser()->getRoles();

    // Administrator override.
    if (in_array('administrator', $current_user_roles)) {
      return parent::getEntityIds();
    }

    $query = \Drupal::entityQuery('member_user');

    if (in_array(FhlbUserRoles::USER_ROLE_MEMBER_ADMIN, $current_user_roles)) {
      $user = \Drupal::entityManager()
        ->getStorage('user')
        ->load(\Drupal::currentUser()->id());

      /** @var \Drupal\fhlb_member_user\Entity\MemberUser $member */
      $member = $user->field_fhlb_member_user->entity;
      if ($member) {
        $member_roles = $member->getAdminMemberRoles();
        $cust_id = $member->cust_id->value;
        if (!empty($cust_id)) {
          $query->condition('cust_id', $cust_id);
        }
        if (!empty($member_roles)) {
          $query->condition('field_fhlb_mem_roles', $member_roles, 'NOT IN');
        }
      }
      return $query->execute();
    }

    return [];
  }

}
