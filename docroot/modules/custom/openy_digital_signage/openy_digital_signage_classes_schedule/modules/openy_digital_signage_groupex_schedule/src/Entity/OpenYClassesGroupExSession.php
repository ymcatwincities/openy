<?php

namespace Drupal\openy_digital_signage_groupex_schedule\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines Digital Signage Classes GroupEx Pro Session entity.
 *
 * @ingroup openy_digital_signage_groupex_schedule
 *
 * @ContentEntityType(
 *   id = "openy_ds_classes_groupex_session",
 *   label = @Translation("Digital Signage Classes GroupEx Pro Session"),
 *
 *   base_table = "openy_ds_classes_groupex_session",
 *   admin_permission = "administer Digital Signage Classes GroupEx Pro Session entities",
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
class OpenYClassesGroupExSession extends ContentEntityBase implements OpenYClassesGroupExSessionInterface {

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
      ->setDescription(t('The ID of the Digital Signage Classes GroupEx Pro Session.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Digital Signage Classes GroupEx Pro Session entity.'))
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

    $fields['groupex_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the GroupEx Pro Session.'))
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

    $fields['date_time'] = BaseFieldDefinition::create('daterange')
      ->setLabel(t('Date and time'))
      ->setDescription(t('The date and time when session happens.'))
      ->setRevisionable(FALSE)
      ->setTranslatable(FALSE)
      ->setRequired(TRUE)
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

    $fields['category'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Category'))
      ->setDescription(t('Category.'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setRevisionable(FALSE)
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('view', FALSE)
      ->setDisplayConfigurable('form', FALSE);

    $fields['instructor'] = BaseFieldDefinition::create('text')
      ->setLabel(t('Instructor name'))
      ->setDescription(t('Name of an instructor in a branch.'))
      ->setTranslatable(TRUE)
      ->setRevisionable(FALSE)
      ->setDisplayConfigurable('view', FALSE)
      ->setDisplayConfigurable('form', FALSE);

    $fields['original_instructor'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Full instructor name'))
      ->setDescription(t('Name of an instructor in a branch.'))
      ->setTranslatable(TRUE)
      ->setRevisionable(FALSE)
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('view', FALSE)
      ->setDisplayConfigurable('form', FALSE);

    $fields['sub_instructor'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Substitute instructor name'))
      ->setDescription(t('Full name of a substitute instructor in a branch.'))
      ->setRevisionable(FALSE)
      ->setSetting('max_length', 255)
      ->setDisplayConfigurable('view', FALSE)
      ->setDisplayConfigurable('form', FALSE);

    $fields['length'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Length of the session'))
      ->setDescription(t('Length of the session.'))
      ->setDisplayConfigurable('view', FALSE)
      ->setDisplayConfigurable('form', FALSE);

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Session description'))
      ->setDescription(t('Session description.'))
      ->setRevisionable(FALSE)
      ->setDisplayConfigurable('view', FALSE)
      ->setDisplayConfigurable('form', FALSE);

    $fields['session_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Reference to the Digital Signage Classes Session'))
      ->setDescription(t('Store reference to the Digital Signage Classes Session.'))
      ->setRequired(FALSE)
      ->setDefaultValue(NULL)
      ->setSetting('target_type', 'openy_ds_classes_session')
      ->setDisplayConfigurable('view', FALSE)
      ->setDisplayConfigurable('form', FALSE);

    return $fields;
  }

}
