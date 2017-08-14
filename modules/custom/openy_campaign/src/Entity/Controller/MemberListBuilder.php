<?php

namespace Drupal\openy_campaign\Entity\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Provides a list controller for ymca_campaign_member entity.
 *
 * @ingroup openy_campaign_member
 */
class MemberListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   *
   * Building the header and content lines for the Member list.
   *
   * Calling the parent::buildHeader() adds a column for the possible actions
   * and inserts the 'edit' and 'delete' links as defined for the entity type.
   */
  public function buildHeader() {
    $header['id'] = $this->t('Internal ID');
    $header['name'] = $this->t('Name');
    $header['mail'] = $this->t('Email');
    $header['membership_id'] = $this->t('Membership ID');
    $header['is_employee'] = $this->t('Employee');
    $header['checkins'] = $this->t('Checkins');
    $header['created_by_staff'] = $this->t('Created by Staff');
    $header['campaigns'] = $this->t('Campaigns');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\openy_campaign\Entity\Member */
    $row['id'] = $entity->id();
    $row['name'] = $entity->getFullName();
    $row['mail'] = $entity->getEmail();
    $row['membership_id'] = $entity->getMemberId();
    $row['is_employee'] = $entity->isMemberEmployee() ? $this->t('Yes') : $this->t('No');
    $row['checkins'] = $entity->getVisits();
    $row['created_by_staff'] = $entity->isCreatedByStaff() ? $this->t('Yes') : $this->t('No');

    // Get Campaigns for this Member
//    $query = \Drupal::entityQuery('openy_campaign_member_campaign')
//      ->condition('member_id', $entity->id());
//    $res = $query->execute();
//    print_r($res);
    $row['campaigns'] = '';

    return $row + parent::buildRow($entity);
  }

}
