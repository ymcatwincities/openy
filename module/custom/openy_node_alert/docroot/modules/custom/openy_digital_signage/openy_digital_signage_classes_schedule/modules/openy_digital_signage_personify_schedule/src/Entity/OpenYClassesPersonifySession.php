<?php

namespace Drupal\openy_digital_signage_personify_schedule\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines Digital Signage Classes Personify Session entity.
 *
 * @ingroup openy_digital_signage_personify_schedule
 *
 * @ContentEntityType(
 *   id = "openy_ds_class_personify_session",
 *   label = @Translation("Digital Signage Classes Personify Session"),
 *
 *   base_table = "openy_ds_class_personify_session",
 *   admin_permission = "administer Digital Signage Classes Personify Session entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "title",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   }
 * )
 */
class OpenYClassesPersonifySession extends ContentEntityBase implements OpenYClassesPersonifySessionInterface {

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('title')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('title', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Digital Signage Classes Personify Session.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Digital Signage Classes Personify Session entity.'))
      ->setReadOnly(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'))
      ->setReadOnly(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'timestamp',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE);

    $fields['hash'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Hash'))
      ->setDescription(t('Entity hash needed to identify updates in Personify.'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setRevisionable(FALSE)
      ->setDefaultValue(NULL)
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('view', FALSE)
      ->setDisplayConfigurable('form', FALSE);

    $fields['personify_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Personify Session.'))
      ->setReadOnly(TRUE);

    $fields['location'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Location'))
      ->setDescription(t('Reference to a location entity.'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setRevisionable(FALSE)
      ->setSetting('target_type', 'node')
      ->setSetting('handler_settings', [
        'target_bundles' => [
          'location' => 'location',
        ],
      ])
      ->setDisplayConfigurable('view', FALSE)
      ->setDisplayConfigurable('form', FALSE);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Class name'))
      ->setDescription(t('Name of a class.'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setRevisionable(FALSE)
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('view', FALSE)
      ->setDisplayConfigurable('form', FALSE);

    $fields['date'] = BaseFieldDefinition::create('daterange')
      ->setLabel(t('Date and time'))
      ->setDescription(t('The date and time when session starts and expires.'))
      ->setRevisionable(FALSE)
      ->setTranslatable(FALSE)
      ->setRequired(TRUE)
      ->setDisplayConfigurable('view', FALSE)
      ->setDisplayConfigurable('form', FALSE);

    $fields['repeat'] = BaseFieldDefinition::create('text')
      ->setLabel(t('Repeat settings'))
      ->setDescription(t('Repeat settings for the class.'))
      ->setRevisionable(FALSE)
      ->setDisplayConfigurable('view', FALSE)
      ->setDisplayConfigurable('form', FALSE);

    $fields['canceled'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Canceled'))
      ->setDescription(t('Indicates that sessions is canceled or not.'))
      ->setRevisionable(FALSE)
      ->setDefaultValue(FALSE)
      ->setDisplayConfigurable('view', FALSE)
      ->setDisplayConfigurable('form', FALSE);

    $fields['start_time'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Start Time'))
      ->setDescription(t('Start time.'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setRevisionable(FALSE)
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('view', FALSE)
      ->setDisplayConfigurable('form', FALSE);

    $fields['end_time'] = BaseFieldDefinition::create('string')
      ->setLabel(t('End Time'))
      ->setDescription(t('End time.'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setRevisionable(FALSE)
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('view', FALSE)
      ->setDisplayConfigurable('form', FALSE);

    $fields['studio'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Room name'))
      ->setDescription(t('Name of a room in a branch.'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setRevisionable(FALSE)
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('view', FALSE)
      ->setDisplayConfigurable('form', FALSE);

    $fields['instructor'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Instructor name'))
      ->setDescription(t('Name of an instructor in a branch.'))
      ->setTranslatable(TRUE)
      ->setRevisionable(FALSE)
      ->setDisplayConfigurable('view', FALSE)
      ->setDisplayConfigurable('form', FALSE);

    $fields['sub_instructor'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Substitute instructor name'))
      ->setDescription(t('Full name of a substitute instructor in a branch.'))
      ->setRevisionable(FALSE)
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('view', FALSE)
      ->setDisplayConfigurable('form', FALSE);

    $fields['raw_data'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Raw Personify data'))
      ->setDescription(t('Raw Personify data.'))
      ->setRevisionable(FALSE)
      ->setDisplayConfigurable('view', FALSE)
      ->setDisplayConfigurable('form', FALSE);

    $fields['canceled'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Canceled'))
      ->setDescription(t('Indicates that sessions is canceled or not.'))
      ->setRevisionable(FALSE)
      ->setDefaultValue(FALSE)
      ->setDisplayConfigurable('view', FALSE)
      ->setDisplayConfigurable('form', FALSE);

    return $fields;
  }

}
