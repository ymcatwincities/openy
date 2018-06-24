<?php
namespace Drupal\openy_schedules\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Datetime\DateHelper;

/**
 * Defines the repeat entity class.
 * 
 * @ingroup openy_schedules
 *
 * @ContentEntityType(
 *   id = "repeat",
 *   label = @Translation("Repeat"),
 *   bundle_label = @Translation("Repeat type"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\Core\Entity\EntityListBuilder",
 *     "access" = "Drupal\Core\Entity\EntityAccessControlHandler",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\openy_schedules\Form\RepeatForm",
 *       "add" = "Drupal\openy_schedules\Form\RepeatForm",
 *       "edit" = "Drupal\openy_schedules\Form\RepeatForm",
 *       "delete" = "Drupal\openy_schedules\Form\RepeatDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "event_data",
 *   admin_permission = "administer site configuration",
 *   translatable = FALSE,
 *   entity_keys = {
 *     "id" = "id",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/repeat/{repeat}",
 *     "delete-form" = "/admin/structure/repeat/{repeat}/delete",
 *     "edit-form" = "/admin/structure/repeat/{repeat}/edit",
 *     "add-form" = "/admin/structure/repeat/add",
 *   },
 *   fieldable = TRUE
 * )
 */
class Repeat extends ContentEntityBase {
  
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Repeat ID'))
      ->setDescription(t('The repeat ID.'))
      ->setReadOnly(TRUE);

    $fields['start'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Repeat Start'))
      ->setDescription(t('The repeat start.'))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'date',
        'weight' => 2,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'timestamp',
        'weight' => 3,
      ));

    $fields['end'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Repeat End'))
      ->setDescription(t('The repeat end.'))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'date',
        'weight' => 2,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'timestamp',
        'weight' => 3,
      ));

    $fields['repeat_year'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Repeat Year'))
      ->setDescription(t('The repeat year. Leave * to repeat every year.'))
      ->setRequired(TRUE)
      ->setSettings(array(
        'default_value' => '*',
        'max_length' => 11,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => 3,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 3,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['repeat_month'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Repeat Month'))
      ->setDescription(t('The repeat month. Leave * to repeat every month.'))
      ->setRequired(TRUE)
      ->setSettings(array(
        'default_value' => '*',
        'max_length' => 11,
        'allowed_values' => ['*' => '*'] + DateHelper::monthNamesUntranslated(),
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'list_default',
        'weight' => 4,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'options_select',
        'weight' => 4,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['repeat_day'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Repeat Day'))
      ->setDescription(t('The repeat day. Leave * to repeat every day.'))
      ->setRequired(TRUE)
      ->setSettings(array(
        'default_value' => '*',
        'max_length' => 11,
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => 5,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'weight' => 5,
      ))
      ->addConstraint('range_asteriks', [
        'min' => 1,
        'max' => DateHelper::daysInYear(),
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['repeat_week'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Repeat Week'))
      ->setDescription(t('The repeat week. Leave * to repeat every week.'))
      ->setRequired(TRUE)
      ->setSettings(array(
        'default_value' => '*',
        'allowed_values' => ['*' => '*'] + range(1, 52),
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'string',
        'weight' => 6,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
        'settings' => [
          'default_value' => '*',
          'empty' => FALSE,
        ],
        'weight' => 6,
      ))
      ->addConstraint('range_asteriks', [
        'min' => 1,
        'max' => 52,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['repeat_weekday'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Repeat Weekday'))
      ->setDescription(t('The repeat weekday. Leave * to repeat every weekday.'))
      ->setRequired(TRUE)
      ->setSettings(array(
        'default_value' => '*',
        'max_length' => 11,
        'allowed_values' => ['*' => '*'] + DateHelper::weekDaysOrdered(DateHelper::weekDaysUntranslated()),
      ))
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'list_default',
        'weight' => 7,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'options_select',
        'settings' => [
          'default_value' => '*',
          'empty' => FALSE,
        ],
        'weight' => 7,
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }
}