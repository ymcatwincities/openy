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
 *     "list_builder" = "Drupal\openy_digital_signage_classes_schedule\OpenYClassesSessionListBuilder",
 *     "views_data" = "Drupal\openy_digital_signage_classes_schedule\Entity\OpenYClassesSessionViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\openy_digital_signage_classes_schedule\Form\OpenYClassesSessionForm",
 *       "add" = "Drupal\openy_digital_signage_classes_schedule\Form\OpenYClassesSessionForm",
 *       "edit" = "Drupal\openy_digital_signage_classes_schedule\Form\OpenYClassesSessionForm",
 *       "delete" = "Drupal\openy_digital_signage_classes_schedule\Form\OpenYClassesSessionDeleteForm",
 *       "override" = "Drupal\openy_digital_signage_classes_schedule\Form\OpenYClassesSessionOverrideForm",
 *     },
 *     "access" = "Drupal\openy_digital_signage_classes_schedule\OpenYClassesSessionAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\openy_digital_signage_classes_schedule\OpenYClassesSessionHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "openy_ds_classes_session",
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
 *     "canonical" = "/admin/digital-signage/classes/sessions/{openy_ds_classes_session}",
 *     "add-form" = "/admin/digital-signage/classes/sessions/add",
 *     "edit-form" = "/admin/digital-signage/classes/sessions/{openy_ds_classes_session}/edit",
 *     "delete-form" = "/admin/digital-signage/classes/sessions/{openy_ds_classes_session}/delete",
 *     "collection" = "/admin/digital-signage/classes/sessions",
 *     "override" = "/admin/digital-signage/classes/session/{openy_ds_classes_session}/override",
 *   },
 *   field_ui_base_route = "openy_ds_classes_session.settings"
 * )
 */
class OpenYClassesSession extends ContentEntityBase implements OpenYClassesSessionInterface {

  /**
   * List of supported sources for classes sessions.
   *
   * @todo Make them Plugins or allow to alter.
   *
   * @return array
   *   List of sources.
   */
  public static function getSourceValues() {
    return [
      'manually' => t('Manually created'),
      'groupex' => t('GroupEx Pro'),
      'personify' => t('Personify'),
    ];
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
  public function getSource() {
    return $this->get('source')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setSource($source) {
    $this->set('source', $source);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isOverridden() {
    return $this->get('overridden')->value;
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
        'allowed_values' => self::getSourceValues(),
      ])
      ->setDefaultValue('manually')
      ->setDisplayOptions('view', [
        'label' => 'visible',
        'type' => 'string',
        'weight' => -6,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -6,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setRequired(TRUE);

    $fields['source_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Reference to source entity.'))
      ->setDescription(t('Store reference to the source entity.'))
      ->setRevisionable(TRUE)
      ->setRequired(FALSE)
      ->setTranslatable(FALSE)
      ->setDefaultValue(NULL)
      ->setDisplayConfigurable('view', FALSE)
      ->setDisplayConfigurable('form', FALSE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Status'))
      ->setDescription(t('Indicates that sessions is active or disabled.'))
      ->setRevisionable(TRUE)
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

    $fields['date_time'] = BaseFieldDefinition::create('daterange')
      ->setLabel(t('Date and time'))
      ->setDescription(t('The date and time when session happens.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(FALSE)
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'visible',
        'type' => 'daterange_default',
        'weight' => 1,
      ])
      ->setDisplayOptions('form', [
        'type' => 'ds_daterange_default',
        'weight' => 1,
        'settings' => [
          'hide_end_date' => 1,
        ],
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    // Field 'field_session_author' - Author reference.
    // A reference to the author of the session in case if session created
    // manually.

     $fields['room'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Room'))
      ->setDescription(t('Reference to a room/studio.'))
      ->setRequired(FALSE)
      ->setTranslatable(FALSE)
      ->setRevisionable(FALSE)
      ->setSetting('target_type', 'openy_ds_room')
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['instructor'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Instructor name'))
      ->setDescription(t('Name of an instructor in a branch.'))
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

    $fields['overridden'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Overridden'))
      ->setDescription(t('Indicates that entity is overridden manually.'))
      ->setRevisionable(TRUE)
      ->setRequired(FALSE)
      ->setTranslatable(FALSE)
      ->setDefaultValue(FALSE)
      ->setDisplayConfigurable('view', FALSE)
      ->setDisplayConfigurable('form', FALSE);

    $fields['original_session'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Reference to original session'))
      ->setDescription(t('Store reference to original session if it is overridden.'))
      ->setRevisionable(TRUE)
      ->setRequired(FALSE)
      ->setTranslatable(FALSE)
      ->setDefaultValue(NULL)
      ->setSetting('target_type', 'openy_ds_classes_session')
      ->setDisplayConfigurable('view', FALSE)
      ->setDisplayConfigurable('form', FALSE);

    return $fields;
  }

}
