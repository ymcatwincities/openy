<?php

namespace Drupal\openy_digital_signage_screen\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the Digital Signage Screen entity.
 *
 * @ingroup openy_digital_signage
 *
 * @ContentEntityType(
 *   id = "openy_digital_signage_screen",
 *   label = @Translation("Digital Signage Screen"),
 *   handlers = {
 *     "view_builder" = "Drupal\openy_digital_signage_screen\Entity\OpenYScreenViewBuilder",
 *     "list_builder" = "Drupal\openy_digital_signage_screen\OpenYScreenListBuilder",
 *     "views_data" = "Drupal\openy_digital_signage_screen\Entity\OpenYScreenViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\openy_digital_signage_screen\Form\OpenYScreenForm",
 *       "add" = "Drupal\openy_digital_signage_screen\Form\OpenYScreenAddForm",
 *       "edit" = "Drupal\openy_digital_signage_screen\Form\OpenYScreenForm",
 *       "delete" = "Drupal\openy_digital_signage_screen\Form\OpenYScreenDeleteForm",
 *     },
 *     "access" = "Drupal\openy_digital_signage_screen\OpenYScreenAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\openy_digital_signage_screen\OpenYScreenHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "openy_digital_signage_screen",
 *   admin_permission = "administer OpenY Digital Signage Screen entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "title",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/screen/{openy_digital_signage_screen}",
 *     "add-form" = "/admin/digital-signage/screens/add",
 *     "edit-form" = "/admin/digital-signage/screens/{openy_digital_signage_screen}/edit",
 *     "schedule" = "/admin/digital-signage/screens/{openy_digital_signage_screen}/schedule",
 *     "delete-form" = "/admin/digital-signage/screens/{openy_digital_signage_screen}/delete",
 *     "collection" = "/admin/digital-signage/screens/list",
 *   },
 *   field_ui_base_route = "openy_digital_signage_screen.settings"
 * )
 */
class OpenYScreen extends ContentEntityBase implements OpenYScreenInterface {

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
      ->setDescription(t('The ID of the Digital Signage Screen entity.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Digital Signage Screen entity.'))
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
      ->setReadOnly(TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'timestamp',
        'weight' => 0,
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['machine_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Machine name'))
      ->setDescription(t('A machine name will be the an unique identifier for the particular screen. For example, MNDT_1A_DS_North_Entrance.'))
      ->setRequired(TRUE)
      ->setTranslatable(TRUE)
      ->setRevisionable(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('view', [
        'label' => 'visible',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['geo_location'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Geo-location'))
      ->setDescription(t('Indoor location geocode that can be used to physically locate the screen when needed.'))
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'text_default',
        'weight' => -2,
      ))
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', array(
        'type' => 'text_textfield',
        'weight' => -2,
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['orientation'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Orientation'))
      ->setDescription(t('Whether this screen is in landscape or portrait mode, this might have implication to the layouts available to the screen.'))
      ->setSettings([
        'allowed_values' => [
          'landscape' => 'Landscape',
          'portrait' => 'Portrait',
        ],
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'visible',
        'type' => 'string',
        'weight' => -3,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_buttons',
        'weight' => -3,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setRequired(TRUE);

    $fields['placement'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Placement note'))
      ->setDescription(t('Additional notes on the placement of the digital sign. E.g. This is a room entry screen, or this screen has an enclosure.'))
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'text_default',
        'weight' => -2,
      ))
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', array(
        'type' => 'text_textfield',
        'weight' => -2,
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['hardware'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Hardware note'))
      ->setDescription(t('Any hardware notes and description. For example, make and model of the digital sign, serial number, screen size and others.'))
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', array(
        'label' => 'hidden',
        'type' => 'text_default',
        'weight' => -1,
      ))
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('form', array(
        'type' => 'text_textfield',
        'weight' => -1,
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Type'))
      ->setSettings([
        'allowed_values' => [
          'digital_sign' => 'Digital sign',
          'room_entry_screen' => 'Room entry screen',
        ],
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'visible',
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_buttons',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setRequired(TRUE);

    // Location reference.
    // A reference to which branch location this screen belongs to. This will be used in the future when the digital signs feature is extended to other branch locations.

    $fields['screen_schedule'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Schedule'))
      ->setDescription(t('A reference to the assigned schedule.'))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'openy_digital_signage_schedule')
      ->setTranslatable(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'visible',
        'type' => 'node',
        'weight' => 1,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 1,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['fallback_content'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Fallback Content'))
      ->setDescription(t('The Screen Content that is rotated if there is no active content in schedule or schedule is not configured.'))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'node')
      ->setSetting('handler_settings', ['target_bundles' => ['screen_content' => 'screen_content']])
      ->setTranslatable(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'visible',
        'type' => 'node',
        'weight' => 2,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 2,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    return $fields;
  }

}
