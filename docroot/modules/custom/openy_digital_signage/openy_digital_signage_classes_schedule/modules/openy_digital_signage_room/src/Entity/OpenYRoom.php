<?php

namespace Drupal\openy_digital_signage_room\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines Digital Signage Room entity.
 *
 * @ingroup openy_digital_signage_room
 *
 * @ContentEntityType(
 *   id = "openy_ds_room",
 *   label = @Translation("Digital Signage Room"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\openy_digital_signage_room\OpenYRoomListBuilder",
 *     "views_data" = "Drupal\openy_digital_signage_room\Entity\OpenYRoomViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\openy_digital_signage_room\Form\OpenYRoomForm",
 *       "add" = "Drupal\openy_digital_signage_room\Form\OpenYRoomForm",
 *       "edit" = "Drupal\openy_digital_signage_room\Form\OpenYRoomForm",
 *       "delete" = "Drupal\openy_digital_signage_room\Form\OpenYRoomDeleteForm",
 *     },
 *     "access" = "Drupal\openy_digital_signage_room\OpenYRoomAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\openy_digital_signage_room\OpenYRoomHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "openy_ds_room",
 *   admin_permission = "administer Digital Signage Room entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "title",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/digital-signage/rooms/{openy_ds_room}",
 *     "add-form" = "/admin/digital-signage/rooms/add",
 *     "edit-form" = "/admin/digital-signage/rooms/{openy_ds_room}/edit",
 *     "delete-form" = "/admin/digital-signage/rooms/{openy_ds_room}/delete",
 *     "collection" = "/admin/digital-signage/rooms/list",
 *   },
 *   field_ui_base_route = "openy_ds_room.settings"
 * )
 */
class OpenYRoom extends ContentEntityBase implements OpenYRoomInterface {

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

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Room name'))
      ->setDescription(t('The name of the studio or other place.'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setRevisionable(FALSE)
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
      ->setDisplayConfigurable('view', FALSE)
      ->setDisplayConfigurable('form', FALSE);

    $fields['groupex_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('GroupEx Pro ID'))
      ->setDescription(t('The ID used in the GroupEx Pro.'))
      ->setRequired(FALSE)
      ->setTranslatable(FALSE)
      ->setRevisionable(FALSE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'visible',
        'type' => 'string',
        'weight' => 5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['personify_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Personify ID'))
      ->setDescription(t('The ID used in the Personify.'))
      ->setRequired(FALSE)
      ->setTranslatable(FALSE)
      ->setRevisionable(FALSE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'visible',
        'type' => 'string',
        'weight' => 6,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 6,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['location'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Location'))
      ->setDescription(t('Reference to a location entity.'))
      ->setRequired(TRUE)
      ->setTranslatable(FALSE)
      ->setRevisionable(FALSE)
      ->setSetting('target_type', 'node')
      ->setSetting('handler_settings', [
        'target_bundles' => [
          'location' => 'location',
        ],
      ])
      ->setDisplayOptions('view', [
        'label' => 'visible',
        'type' => 'node',
        'weight' => 4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 4,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Status'))
      ->setDescription(t('Indicates that the room is active in the system.'))
      ->setRevisionable(FALSE)
      ->setTranslatable(FALSE)
      ->setRequired(TRUE)
      ->setSetting('on_label', 'Enabled')
      ->setSetting('off_label', 'Disabled')
      ->setDisplayOptions('view', [
        'label' => 'visible',
        'type' => 'boolean',
        'weight' => 1,
        'settings' => [
          'format' => 'default',
        ],
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_buttons',
        'weight' => 1,
      ])
      ->setDefaultValue(TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Room description'))
      ->setDescription(t('Room description.'))
      ->setRevisionable(FALSE)
      ->setDisplayOptions('form', array(
        'type' => 'text_textfield',
        'weight' => 0,
      ))
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    return $fields;
  }

}
