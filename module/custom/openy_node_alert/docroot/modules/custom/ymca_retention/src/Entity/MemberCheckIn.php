<?php

namespace Drupal\ymca_retention\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;

use Drupal\ymca_retention\MemberCheckInInterface;

/**
 * Defines the Member Check-in entity.
 *
 * @ingroup ymca_retention
 *
 * @ContentEntityType(
 *   id = "ymca_retention_member_checkin",
 *   label = @Translation("Member Check-in entity"),
 *   handlers = {
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "ymca_retention_member_checkin",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "id",
 *   },
 * )
 */
class MemberCheckIn extends ContentEntityBase implements MemberCheckInInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    // Standard field, used as unique if primary index.
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Member Check-in entity.'))
      ->setReadOnly(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time the record was created.'));

    $fields['date'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Date'))
      ->setDescription(t('The timestamp of the day when check-in was logged.'));

    $fields['member'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Member ID'))
      ->setDescription(t('The member ID.'))
      ->setSettings([
        'target_type' => 'ymca_retention_member',
        'default_value' => 0,
      ]);

    $fields['checkin'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Status of check-in for the day.'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'boolean',
        'weight' => -1,
      ]);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getMember() {
    return $this->get('member')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getDate() {
    return $this->get('date')->value;
  }

}
