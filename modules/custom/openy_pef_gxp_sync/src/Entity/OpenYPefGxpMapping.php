<?php

namespace Drupal\openy_pef_gxp_sync\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the repeat entity class.
 *
 * @ContentEntityType(
 *   id = "openy_pef_gxp_mapping",
 *   label = @Translation("OpenY PEF GXP Mapping"),
 *   base_table = "openy_pef_gxp_mapping",
 *   data_table = "openy_pef_gxp_mapping",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid"
 *   },
 *   translatable = FALSE,
 *   fieldable = FALSE,
 *   admin_permission = "administer site configuration",
 * )
 */
class OpenYPefGxpMapping extends ContentEntityBase implements OpenYPefGxpMappingInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Repeat ID'))
      ->setDescription(t('The repeat ID.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the entity.'))
      ->setReadOnly(TRUE);

    $fields['session'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Session'))
      ->setDescription(t('Reference to the Session.'))
      ->setSetting('target_type', 'node')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'entity_reference_label',
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
      ]);

    $fields['product_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Class ID'))
      ->setDescription(t('Used to map source Class ID.'))
      ->setRequired(TRUE)
      ->setSettings([
        'default_value' => '',
        'max_length' => 32,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['location_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Location ID'))
      ->setDescription(t('Used to map source Location ID.'))
      ->setRequired(TRUE)
      ->setSettings([
        'default_value' => '',
        'max_length' => 32,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 1,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
