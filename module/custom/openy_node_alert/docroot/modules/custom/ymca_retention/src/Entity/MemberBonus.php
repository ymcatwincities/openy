<?php

namespace Drupal\ymca_retention\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;

use Drupal\ymca_retention\MemberBonusInterface;

/**
 * Defines the Member Bonus entity.
 *
 * @ingroup ymca_retention
 *
 * @ContentEntityType(
 *   id = "ymca_retention_member_bonus",
 *   label = @Translation("Member Bonus entity"),
 *   handlers = {
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "ymca_retention_member_bonus",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "id",
 *   },
 * )
 */
class MemberBonus extends ContentEntityBase implements MemberBonusInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    // Standard field, used as unique if primary index.
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Member Bonus entity.'))
      ->setReadOnly(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time the record was created.'));

    $fields['date'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Date'))
      ->setDescription(t('The timestamp of the day when bonus was claimed.'));

    $fields['member'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Member ID'))
      ->setDescription(t('The member ID.'))
      ->setSettings([
        'target_type' => 'ymca_retention_member',
        'default_value' => 0,
      ]);

    $fields['bonus_code'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Bonus code'))
      ->setDescription(t('Claimed bonus code.'))
      ->setDefaultValue('')
      ->setSettings([
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
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

  /**
   * {@inheritdoc}
   */
  public function getBonusCode() {
    return $this->get('bonus_code')->value;
  }

}
