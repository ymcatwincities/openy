<?php

namespace Drupal\openy_digital_signage_classes_schedule\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines Digital Signage Classes Session entity.
 *
 * @ingroup openy_digital_signage_classes_schedule
 *
 * @ContentEntityType(
 *   id = "openy_ds_classes_session",
 *   label = @Translation("Digital Signage Classes Session"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\openy_digital_signage_schedule\OpenYClassesSessionListBuilder",
 *     "views_data" = "Drupal\openy_digital_signage_schedule\Entity\OpenYClassesSessionViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\openy_digital_signage_classes_schedule\Form\OpenYClassesSessionForm",
 *       "add" = "Drupal\openy_digital_signage_classes_schedule\Form\OpenYClassesSessionForm",
 *       "edit" = "Drupal\openy_digital_signage_classes_schedule\Form\OpenYClassesSessionForm",
 *       "delete" = "Drupal\openy_digital_signage_classes_schedule\Form\OpenYClassesSessionDeleteForm",
 *     },
 *     "access" = "Drupal\openy_digital_signage_classes_schedule\OpenYClassesSessionAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\openy_digital_signage_classes_schedule\OpenYClassesSessionHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "openy_ds_classes_session",
 *   data_table = "openy_ds_classes_session_field_data",
 *   admin_permission = "administer Digital Signage Classes Session entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "title",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/digital-signage/classes/{openy_ds_classes_session}",
 *     "add-form" = "/admin/digital-signage/classes/add",
 *     "edit-form" = "/admin/digital-signage/classes/{openy_ds_classes_session}/edit",
 *     "delete-form" = "/admin/digital-signage/classes/{openy_ds_classes_session}/delete",
 *     "collection" = "/admin/digital-signage/classes/list",
 *   },
 *   field_ui_base_route = "openy_ds_classes_session_settings.settings"
 * )
 */
class OpenYClassesSession extends ContentEntityBase implements OpenYClassesSessionInterface {

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
      ->setDescription(t('The ID of the Digital Signage Classes Session entity.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Digital Signage Classes Session entity.'))
      ->setReadOnly(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'))
      ->setReadOnly(TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'timestamp',
        'weight' => 0,
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['source'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Source'))
      ->setDescription(t('Source of the entity.'))
      ->setSettings([
        'allowed_values' => [
          'manually' => 'Manually created',
          'groupex' => 'GroupEx Pro',
          'personify' => 'Personify',
        ],
      ])
      ->setDefaultValue('manually')
      ->setDisplayOptions('view', [
        'label' => 'visible',
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setRequired(TRUE);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Class name'))
      ->setDescription(t('Name of a class.'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'visible',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['time_slot'] = BaseFieldDefinition::create('daterange')
      ->setLabel(t('Time slot'))
      ->setDescription(t('When this class will be, for example from 10:00 to 11:00am.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(FALSE)
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'visible',
        'type' => 'daterange_default',
        'weight' => 1,
      ])
      ->setDisplayOptions('form', [
        'type' => 'daterange_time_only',
        'weight' => 1,
        'settings' => [
          'increment' => '15',
          'date_order' => 'YMD',
          'time_type' => '24',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    // Location reference.
    // A reference to which branch location this screen belongs to.
    // This will be used in the future when the digital signs feature is
    // extended to other branch locations.

    $fields['room_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Room name'))
      ->setDescription(t('Name of a room in a branch.'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'visible',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['instructor'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Instructor name'))
      ->setDescription(t('Name of an instructor in a branch.'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'visible',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['sub_instructor'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Substitute instructor name'))
      ->setDescription(t('Name of a substitute instructor in a branch.'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'visible',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    return $fields;
  }

}
