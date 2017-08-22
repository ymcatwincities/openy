<?php
/**
 *
 */

namespace Drupal\openy_campaign\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;

use Drupal\openy_campaign\MemberCampaignActivityInterface;

/**
 * Track participant's check-ins and activities.
 *
 * @ingroup openy_campaign
 *
 * @ContentEntityType(
 *   id = "openy_member_campaign_activity",
 *   label = @Translation("Member Activity entity"),
 *   handlers = {
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "openy_member_campaign_activity",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "id",
 *   },
 * )
 */
class MemberCampaignActivity  extends ContentEntityBase implements MemberCampaignActivityInterface {

  /**
   * Types of entity.
   */
  const TYPE_CHECKIN  = 0;
  const TYPE_ACTIVITY = 1;

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

    $fields['type'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Either facility check in or activity.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'integer',
        'weight' => -1,
      ]);

    $fields['activity'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Activity term'))
      ->setDescription(t('What activity this record is for.'))
      ->setSettings([
        'target_type' => 'taxonomy_term',
        'default_value' => 0,
      ]);

    return $fields;
  }

  /**
   * Get Existing Activities
   *
   * @param int $memberCampaignId MemberCampaign entity ID
   * @param \DateTime $date Date
   * @param array $activityIds Activities IDs
   *
   * @return array|int
   */
  public static function getExistingActivities($memberCampaignId, $date, $activityIds) {
    return \Drupal::entityQuery('openy_member_campaign_activity')
      ->condition('member_campaign', $memberCampaignId)
      ->condition('type', MemberCampaignActivity::TYPE_ACTIVITY)
      ->condition('date', $date->format('U'))
      ->condition('activity', $activityIds, 'IN')
      ->execute();
  }
}
