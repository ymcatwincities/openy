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
class MemberCheckin  extends ContentEntityBase implements MemberCampaignActivityInterface {

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

  public static function getFacilityCheckIns($memberId, $startDate, $endDate) {
    return \Drupal::entityQuery('openy_campaign_member_checkin')
      ->condition('member', $memberId)
      ->condition('date', $startDate->format('U'), '>=')
      ->condition('date', $endDate->format('U'), '<')
      ->execute();
  }

}
