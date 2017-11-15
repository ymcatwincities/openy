<?php

/**
 * @file
 * Definition of Drupal\d8views\Plugin\views\field\MemberCampaignCheckins
 */

namespace Drupal\openy_campaign\Plugin\views\field;

use Drupal\openy_campaign\Entity\MemberCampaign;
use Drupal\openy_campaign\Entity\MemberCheckin;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Checkins handler for the member entity.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("member_campaign_checkins")
 */
class MemberCampaignCheckins extends FieldPluginBase {

  /**
   * @{inheritdoc}
   */
  public function query() {
    // Leave empty to avoid a query on this field.
  }

  /**
   * @{inheritdoc}
   */
  public function render(ResultRow $values) {
    /** @var \Drupal\openy_campaign\Entity\MemberCampaign $entity */
    if ($values->_entity instanceof MemberCampaign) {
      $entity = $values->_entity;
    } else {
      $relationship_entities = $values->_relationship_entities;
      $entity = $relationship_entities['member_campaign'];
    }

    /** @var \Drupal\openy_campaign\Entity\Member $member */
    $member = $entity->getMember();

    if (empty($member)) {
      return;
    }
    
    /** @var \Drupal\node\Entity\Node $campaign */
    $campaign = $entity->getCampaign();

    /** @var \DateTime $start */
    $start = $campaign->field_campaign_start_date->date;
    // Reset time to include the current day to the list.
    $start->setTime(0, 0, 0);

    /** @var \DateTime $end */
    $end = $campaign->field_campaign_end_date->date;

    $facilityCheckInIds = MemberCheckin::getFacilityCheckIns($member->id(), $start, $end);

    return count($facilityCheckInIds);

  }
}
