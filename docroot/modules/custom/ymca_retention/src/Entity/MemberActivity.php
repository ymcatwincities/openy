<?php

namespace Drupal\ymca_retention\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\ymca_retention\MemberActivityInterface;

/**
 * Defines the Member Activity entity.
 *
 * @ingroup ymca_retention
 *
 * @ContentEntityType(
 *   id = "ymca_retention_member_activity",
 *   label = @Translation("Member Activity entity"),
 *   base_table = "ymca_retention_member_activity",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "id",
 *   },
 * )
 */
class MemberActivity extends ContentEntityBase implements MemberActivityInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    // Standard field, used as unique if primary index.
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Member Activity entity.'))
      ->setReadOnly(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time the record was created.'));

    $fields['timestamp'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Activity time'))
      ->setDescription(t('The timestamp of the activity.'));

    $fields['member'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Member ID'))
      ->setDescription(t('The member ID of the activity owner.'))
      ->setSettings([
        'target_type' => 'ymca_retention_member',
        'default_value' => 0,
      ]);

    $fields['activity_type'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Activity type'))
      ->setDescription(t('The term ID of the activity type.'))
      ->setSettings([
        'target_type' => 'taxonomy_term',
        'default_value' => 0,
      ]);

    return $fields;
  }

}
