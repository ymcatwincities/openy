<?php

namespace Drupal\openy_session_instance\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the Session Instance entity.
 *
 * @ingroup openy_session_instance
 *
 * @ContentEntityType(
 *   id = "session_instance",
 *   label = @Translation("Session Instance"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\openy_session_instance\SessionInstanceListBuilder",
 *     "views_data" = "Drupal\openy_session_instance\Entity\SessionInstanceViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\openy_session_instance\Form\SessionInstanceForm",
 *       "add" = "Drupal\openy_session_instance\Form\SessionInstanceForm",
 *       "edit" = "Drupal\openy_session_instance\Form\SessionInstanceForm",
 *       "delete" = "Drupal\openy_session_instance\Form\SessionInstanceDeleteForm",
 *     },
 *     "access" = "Drupal\openy_session_instance\SessionInstanceAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\openy_session_instance\SessionInstanceHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "session_instance",
 *   data_table = "session_instance_field_data",
 *   admin_permission = "administer session instance entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "title",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *     "timestamp" = "timestamp",
 *     "timestamp_to" = "timestamp_to",
 *     "class" = "class",
 *     "location" = "location"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/session_instance/{session_instance}",
 *     "add-form" = "/admin/structure/session_instance/add",
 *     "edit-form" = "/admin/structure/session_instance/{session_instance}/edit",
 *     "delete-form" = "/admin/structure/session_instance/{session_instance}/delete",
 *     "collection" = "/admin/structure/session_instance",
 *   },
 *   field_ui_base_route = "session_instance.settings"
 * )
 */
class SessionInstance extends ContentEntityBase implements SessionInstanceInterface {

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
  }

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
  public function getTimestamp() {
    return $this->get('timestamp')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getTimestampTo() {
    return $this->get('timestamp_to')->value;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Session Instance entity.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Session Instance entity.'))
      ->setReadOnly(TRUE);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
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

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'))
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'timestamp',
        'weight' => 0,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'datetime_timestamp',
        'weight' => 10,
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['timestamp'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('From'))
      ->setDescription(t('The time that the Session Instance begins.'))
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'timestamp',
        'weight' => 0,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'datetime_timestamp',
        'weight' => 10,
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['timestamp_to'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('To'))
      ->setDescription(t('The time that the Session Instance ends.'))
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'timestamp',
        'weight' => 0,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'datetime_timestamp',
        'weight' => 10,
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['session'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Session'))
      ->setDescription(t('The Session that created the Session Instance.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'node')
      ->setSetting('handler_settings', ['target_bundles' => ['session' => 'session']])
      ->setTranslatable(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'visible',
        'type' => 'node',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 6,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['class'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Class'))
      ->setDescription(t('The Class node, which the Session Instance is related to.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'node')
      ->setSetting('handler_settings', ['target_bundles' => ['class' => 'class']])
      ->setTranslatable(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'visible',
        'type' => 'node',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['location'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Location'))
      ->setDescription(t('The Branch or Camp node, which the Session Instance is related to.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'node')
      ->setSetting('handler_settings', [
        'target_bundles' => [
          'branch' => 'branch',
          'camp' => 'camp',
        ],
      ])
      ->setTranslatable(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'visible',
        'type' => 'node',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['min_age'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Minimum age'))
      ->setSetting('unsigned', FALSE);

    $fields['max_age'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Minimum age'))
      ->setSetting('unsigned', FALSE);

    return $fields;
  }

}
