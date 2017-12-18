<?php

namespace Drupal\openy_digital_signage_schedule\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the OpenY Digital Signage Schedule entity.
 *
 * @ingroup openy_digital_signage
 *
 * @ContentEntityType(
 *   id = "openy_digital_signage_schedule",
 *   label = @Translation("Digital Signage Schedule"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\openy_digital_signage_schedule\OpenYScheduleListBuilder",
 *     "views_data" = "Drupal\openy_digital_signage_schedule\Entity\OpenYScheduleViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\openy_digital_signage_schedule\Form\OpenYScheduleForm",
 *       "add" = "Drupal\openy_digital_signage_schedule\Form\OpenYScheduleForm",
 *       "edit" = "Drupal\openy_digital_signage_schedule\Form\OpenYScheduleForm",
 *       "delete" = "Drupal\openy_digital_signage_schedule\Form\OpenYScheduleDeleteForm",
 *     },
 *     "access" = "Drupal\openy_digital_signage_schedule\OpenYScheduleAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\openy_digital_signage_schedule\OpenYScheduleHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "openy_digital_signage_schedule",
 *   data_table = "openy_digital_signage_schedule_field_data",
 *   admin_permission = "administer OpenY Digital Signage Schedule entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "title",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/digital-signage/schedules/{openy_digital_signage_schedule}",
 *     "add-form" = "/admin/digital-signage/schedules/add",
 *     "edit-form" = "/admin/digital-signage/schedules/{openy_digital_signage_schedule}/edit",
 *     "delete-form" = "/admin/digital-signage/schedules/{openy_digital_signage_schedule}/delete",
 *     "collection" = "/admin/digital-signage/schedules",
 *   },
 *   field_ui_base_route = "openy_digital_signage_schedule.settings"
 * )
 */
class OpenYSchedule extends ContentEntityBase implements OpenYScheduleInterface {

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
      ->setDescription(t('The ID of the Digital Signage Schedule entity.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Digital Signage Schedule entity.'))
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

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Description'))
      ->setDescription(t('Extended description of the schedule.'))
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

    return $fields;
  }

}
