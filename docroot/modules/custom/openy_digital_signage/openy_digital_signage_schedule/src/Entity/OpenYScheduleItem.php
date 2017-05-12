<?php

namespace Drupal\openy_digital_signage_schedule\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the OpenY Digital Signage Schedule Item entity.
 *
 * @ingroup openy_digital_signage
 *
 * @ContentEntityType(
 *   id = "openy_digital_signage_sch_item",
 *   label = @Translation("OpenY Digital Signage Schedule Item"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\openy_digital_signage_schedule\OpenYScheduleItemListBuilder",
 *     "views_data" = "Drupal\openy_digital_signage_schedule\Entity\OpenYScheduleItemViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\openy_digital_signage_schedule\Form\OpenYScheduleItemForm",
 *       "add" = "Drupal\openy_digital_signage_schedule\Form\OpenYScheduleItemForm",
 *       "edit" = "Drupal\openy_digital_signage_schedule\Form\OpenYScheduleItemForm",
 *       "delete" = "Drupal\openy_digital_signage_schedule\Form\OpenYScheduleItemDeleteForm",
 *     },
 *     "access" = "Drupal\openy_digital_signage_schedule\OpenYScheduleItemAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\openy_digital_signage_schedule\OpenYScheduleItemHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "openy_digital_signage_sch_item",
 *   data_table = "openy_digital_signage_sch_item_field_data",
 *   admin_permission = "administer OpenY Digital Signage Schedule Item entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "title",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/digital-signage/schedule-items/{openy_digital_signage_sch_item}",
 *     "add-form" = "/admin/digital-signage/schedule-items/add",
 *     "edit-form" = "/admin/digital-signage/schedule-items/{openy_digital_signage_sch_item}/edit",
 *     "delete-form" = "/admin/digital-signage/schedule-items/{openy_digital_signage_sch_item}/delete",
 *     "collection" = "/admin/digital-signage/schedule-items/list",
 *   },
 *   field_ui_base_route = "openy_digital_signage_sch_item.settings"
 * )
 */
class OpenYScheduleItem extends ContentEntityBase implements OpenYScheduleItemInterface {

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
      ->setDescription(t('The ID of the OpenY Digital Signage Schedule Item entity.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the OpenY Digital Signage Schedule Item entity.'))
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

    $fields['schedule'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Schedule'))
      ->setDescription(t('A reference to the assigned schedule.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'openy_digital_signage_schedule')
      //->setSetting('handler_settings', ['target_bundles' => ['screen_content' => 'screen_content']])
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

    $fields['time_slot'] = BaseFieldDefinition::create('daterange')
      ->setLabel(t('Time slot'))
      ->setDescription(t('When this schedule item will be active, for example from 10:00 to 11:00am.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(FALSE)
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


    $fields['content'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Content'))
      ->setDescription(t('The Screen Content that is rotated for this time slot.'))
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
