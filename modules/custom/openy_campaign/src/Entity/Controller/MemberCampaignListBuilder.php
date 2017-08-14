<?php

namespace Drupal\openy_campaign\Entity\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Provides a list controller for ymca_campaign_member entity.
 *
 * @ingroup openy_campaign_member
 */
class MemberCampaignListBuilder extends EntityListBuilder {

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
    $header['member_id'] = $this->t('Member ID');
    $header['name'] = $this->t('Member name');
    $header['membership_id'] = $this->t('Membership ID');
    $header['campaign_id'] = $this->t('Campaign ID');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\openy_campaign\Entity\MemberCampaign */
    $row['id'] = $entity->id();
    $row['member_id'] = $entity->getMemberId();
    $row['campaign_id'] = $entity->getCampaignId();

    // Get Member entity
    $query = \Drupal::entityQuery('openy_campaign_member')
      ->condition('member_id', $entity->id());
    /* @var $member \Drupal\openy_campaign\Entity\Member */
    $member = $query->execute();
    print_r($member);

    $row['name'] = $member->getFullName();
    $row['membership_id'] = $entity->getMemberId();

    // Get Campaigns for this Member
//    $query = \Drupal::entityQuery('openy_campaign_member_campaign')
//      ->condition('member_id', $entity->id());
//    $res = $query->execute();
//    print_r($res);

    return $row + parent::buildRow($entity);
  }

}
