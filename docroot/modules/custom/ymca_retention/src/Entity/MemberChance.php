<?php

namespace Drupal\ymca_retention\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\ymca_retention\MemberChanceInterface;

/**
 * Defines the Member Chance entity.
 *
 * @ingroup ymca_retention
 *
 * @ContentEntityType(
 *   id = "ymca_retention_member_chance",
 *   label = @Translation("Member Chance entity"),
 *   handlers = {
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "ymca_retention_member_chance",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "id",
 *   },
 * )
 */
class MemberChance extends ContentEntityBase implements MemberChanceInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    // Standard field, used as unique if primary index.
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Member Chance entity.'))
      ->setReadOnly(TRUE);

    $fields['type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Chance type'))
      ->setRequired(TRUE)
      ->setReadOnly(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time the record was created.'));

    $fields['timestamp'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Timestamp'))
      ->setDescription(t('The timestamp with the date the chance is granted for.'))
      ->setSettings([
        'default_value' => 0,
      ]);

    $fields['played'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Played'))
      ->setDescription(t('The timestamp when the chance was played.'))
      ->setSettings([
        'default_value' => 0,
      ]);

    $fields['member'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Member ID'))
      ->setDescription(t('The member ID.'))
      ->setSettings([
        'target_type' => 'ymca_retention_member',
        'default_value' => 0,
      ]);

    $fields['winner'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Indicate if this chance won or not.'))
      ->setDefaultValue(FALSE)
      ->setSettings([
        'on_label' => t('WON'),
        'off_label' => t('LOST'),
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['message'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Message'))
      ->setDescription(t('Message to show to the user.'))
      ->setSettings([
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ]);

    // TODO: add some relation to tango card entity?

    return $fields;
  }

}
