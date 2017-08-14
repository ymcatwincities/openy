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

    // Get Member entity
    $memberRes = \Drupal::entityQuery('openy_campaign_member')
      ->condition('id', $entity->getMemberId())->execute();
    $memberStorage = \Drupal::entityTypeManager()
      ->getStorage('openy_campaign_member');
    /* @var $member \Drupal\openy_campaign\Entity\Member */
    $member = $memberStorage->load(reset($memberRes));

    $row['name'] = $member->getFullName();
    $row['membership_id'] = $member->getMemberId();
    $row['campaign_id'] = $entity->getCampaignId();

    return $row + parent::buildRow($entity);
  }

}
