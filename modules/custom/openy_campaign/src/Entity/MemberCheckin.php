<?php

namespace Drupal\openy_campaign\Entity;

use Drupal\Core\Entity\Entity;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\openy_campaign\MemberCampaignActivityInterface;

/**
 * Track participant's check-ins to facilities.
 *
 * @ingroup openy_campaign
 *
 * @ContentEntityType(
 *   id = "openy_campaign_member_checkin",
 *   label = @Translation("Member Checkin entity"),
 *   handlers = {
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "openy_campaign_member_checkin",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "id",
 *   },
 * )
 */
class MemberCheckin extends ContentEntityBase implements MemberCampaignActivityInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    // Standard field, used as unique if primary index.
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Checkin entity.'))
      ->setReadOnly(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time the record was created.'));

    $fields['date'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Date'))
      ->setDescription(t('The timestamp for the day when activity was logged.'));

    $fields['member'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Member'))
      ->setDescription(t('Member who checked in at facility.'))
      ->setSettings([
        'target_type' => 'openy_campaign_member',
        'default_value' => 0,
      ]);

    return $fields;
  }

  /**
   * Get Member check-ins for a period.
   *
   * @param int $memberId
   *   Member id.
   * @param \DateTime $startDate
   * @param \DateTime $endDate
   *
   * @return array|int
   */
  public static function getFacilityCheckIns($memberId, $startDate, $endDate) {
    return \Drupal::entityQuery('openy_campaign_member_checkin')
      ->condition('member', $memberId)
      ->condition('date', $startDate->format('U'), '>=')
      ->condition('date', $endDate->format('U'), '<')
      ->execute();
  }

  /**
   * Create Game opportunity and utilization activity while checkin record is created.
   */
  public function save() {
    $return = parent::save();

    // Create Game opportunity.
    // Get the list of all active campaigns.
    $currentDate = new DrupalDateTime('now');
    $currentDate->setTimezone(new \DateTimezone(DATETIME_STORAGE_TIMEZONE));
    $formatted = $currentDate->format(DATETIME_DATETIME_STORAGE_FORMAT);

    $campaigns = \Drupal::entityQuery('node')
      ->condition('type', 'campaign')
      ->condition('field_campaign_start_date', $formatted, '<=')
      ->condition('field_campaign_end_date', $formatted, '>=')
      ->execute();

    $campaignMembers = \Drupal::entityQuery('openy_campaign_member_campaign')
      ->condition('campaign', array_values($campaigns), 'IN')
      ->condition('member', $this->get('member')->entity->id())
      ->execute();

    foreach (array_keys($campaignMembers) as $campaignMemberId) {
      $isAllowedToCreateAnEntry = FALSE;

      $memberCampaign = MemberCampaign::load($campaignMemberId);
      /** @var \Drupal\node\NodeInterface $campaign */
      $campaign = $memberCampaign->getCampaign();
      foreach ($campaign->get('field_ways_to_earn_entries')->getValue() as $item) {
        if ($item['value'] == MemberGame::TYPE_CHECKIN) {
          $isAllowedToCreateAnEntry = TRUE;
          break;
        }
      }

      if ($isAllowedToCreateAnEntry) {
        $game = MemberGame::create([
          'member' => $campaignMemberId,
          'chance_type' => MemberGame::TYPE_CHECKIN,
        ]);

        $game->save();
      }

      // Create an utilization activity record.
      $utilizationActivities = $campaign->get('field_utilization_activities')->getValue();
      $activities = [];
      foreach ($utilizationActivities as $utilizationActivity) {
        $activities[] = $utilizationActivity['value'];
      }

      if (in_array('visiting', $activities)) {
        $loadedEntity = \Drupal::entityQuery('openy_campaign_util_activity')
          ->condition('member_campaign', $campaignMemberId)
          ->execute();

        if (empty($loadedEntity)) {
          $preparedActivityData = [
            'member_campaign' => $campaignMemberId,
            'created' => time(),
            'activity_type' => 'visiting'
          ];
          $campaignUtilizationActivity = CampaignUtilizationActivitiy::create($preparedActivityData);
          $campaignUtilizationActivity->save();
        }
      }

    }

    return $return;
  }

}
