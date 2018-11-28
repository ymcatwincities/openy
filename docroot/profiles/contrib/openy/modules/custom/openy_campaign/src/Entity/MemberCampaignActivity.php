<?php

namespace Drupal\openy_campaign\Entity;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;

use Drupal\openy_campaign\MemberCampaignActivityInterface;

/**
 * Track participant's activities.
 *
 * @ingroup openy_campaign
 *
 * @ContentEntityType(
 *   id = "openy_campaign_memb_camp_actv",
 *   label = @Translation("Member Activity entity"),
 *   handlers = {
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "openy_campaign_memb_camp_actv",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "id",
 *   },
 * )
 */
class MemberCampaignActivity extends ContentEntityBase implements MemberCampaignActivityInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    // Standard field, used as unique if primary index.
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Activity entity.'))
      ->setReadOnly(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time the record was created.'));

    $fields['date'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Date'))
      ->setDescription(t('The timestamp for the day when activity was logged.'));

    $fields['member_campaign'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Member Campaign'))
      ->setDescription(t('The member campaign.'))
      ->setSettings([
        'target_type' => 'openy_campaign_member_campaign',
        'default_value' => 0,
      ]);

    $fields['activity'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Activity term'))
      ->setDescription(t('What activity this record is for.'))
      ->setSettings([
        'target_type' => 'taxonomy_term',
        'default_value' => 0,
      ]);

    $fields['count'] = BaseFieldDefinition::create('decimal')
      ->setLabel(t('Count'))
      ->setDescription(t('Activity counter value.'))
      ->setSettings([
        'default_value' => 0.0,
      ]);

    return $fields;
  }

  /**
   * Get Existing Activities.
   *
   * @param int $memberCampaignId
   *   MemberCampaign entity ID.
   * @param \DateTime $date
   *   Date.
   * @param array|NULL $activityIds
   *   Activities IDs.
   *
   * @return array|int
   */
  public static function getExistingActivities($memberCampaignId, $date, $activityIds = []) {
    $query = \Drupal::entityQuery('openy_campaign_memb_camp_actv')
      ->condition('member_campaign', $memberCampaignId)
      ->condition('date', $date->format('U'));
    if (!empty($activityIds)) {
      $query->condition('activity', $activityIds, 'IN');
    }
    return $query->execute();
  }

  /**
   * Get an array of the activities tracked by user.
   *
   * @param $memberCampaignId
   *
   * @return array
   */
  public static function getTrackedActivities($memberCampaignId) {
    $query = \Drupal::entityQuery('openy_campaign_memb_camp_actv')
      ->condition('member_campaign', $memberCampaignId)
      ->condition('count', '0', '>');
    $activityIds = $query->execute();

    $existingActivitiesEntities = \Drupal::service('entity_type.manager')->getStorage('openy_campaign_memb_camp_actv')->loadMultiple($activityIds);
    /** @var \Drupal\openy_campaign\Entity\MemberCampaignActivity $activity */
    $activity_count_names = [];
    foreach ($existingActivitiesEntities as $activity) {
      $name = $activity->activity->entity->get('name')->value;
      $desc = $activity->activity->entity->get('description')->value;
      // Count activities by name.
      $activity_count_names[$name]['name'] = $name;
      $activity_count_names[$name]['desc'] = $desc;
      $activity_count_names[$name]['count'] += $activity->count->value;
    }

    return $activity_count_names;
  }

  /**
   * {@inheritdoc}
   */
  public function save() {
    $return = parent::save();
    /** @var \Drupal\openy_campaign\Entity\MemberCampaign $memberCampaign */
    $memberCampaign = $this->get('member_campaign')->entity;

    $isAllowedToCreateAnEntry = FALSE;

    /** @var \Drupal\node\NodeInterface $campaign */
    $campaign = $memberCampaign->getCampaign();
    foreach ($campaign->get('field_ways_to_earn_entries')->getValue() as $item) {
      if ($item['value'] == MemberGame::TYPE_ACTIVITY) {
        $isAllowedToCreateAnEntry = TRUE;
        break;
      }
    }

    if ($isAllowedToCreateAnEntry) {
      $date = $this->get('date')->value;

      $beginOfDay = strtotime("midnight", $date);
      $endOfDay = strtotime("tomorrow", $date) - 1;

      /** @var \Drupal\taxonomy\Entity\Term $activityCategory */
      $activityCategory = $this->get('activity')->entity;
      $ancestors = \Drupal::service('entity_type.manager')->getStorage("taxonomy_term")->loadAllParents($activityCategory->id());
      /** @var \Drupal\taxonomy\Entity\Term $activityTopCategory */
      $activityTopCategory = array_pop($ancestors);

      $query = \Drupal::entityQuery('openy_campaign_member_game')
        ->condition('event_date', $beginOfDay, '>=')
        ->condition('event_date', $endOfDay, '<=')
        ->condition('member', $memberCampaign->id(), '=')
        ->condition('chance_type', MemberGame::TYPE_ACTIVITY, '=')
        ->condition('chance_activity', $activityTopCategory->id(), '=');

      $games = $query->execute();

      if (empty($games)) {
        // Create Instant-Win game chance.
        $game = MemberGame::create([
          'member' => $memberCampaign->id(),
          'chance_type' => MemberGame::TYPE_ACTIVITY,
          'chance_activity' => $activityTopCategory->id(),
          'event_date' => $date,
        ]);
        $game->save();
      }
    }

    return $return;

  }

}
