<?php
namespace Drupal\openy_repeat\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Datetime\DateHelper;

/**
 * Defines the repeat entity class.
 *
 * @ingroup openy_repeat
 *
 * @ContentEntityType(
 *   id = "repeat",
 *   label = @Translation("Repeat"),
 *   base_table = "repeat_event",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid"
 *   },
 *   translatable = FALSE,
 *   fieldable = FALSE,
 *   admin_permission = "administer site configuration",
 * )
 */
class Repeat extends ContentEntityBase implements RepeatInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Repeat ID'))
      ->setDescription(t('The repeat ID.'))
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the entity.'))
      ->setReadOnly(TRUE);

    $fields['session'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Session'))
      ->setDescription(t('Reference to the Session.'))
      ->setSetting('target_type', 'node')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'entity_reference_label',
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete'
      ]);

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

    $fields['year'] = BaseFieldDefinition::create('string')
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

    $fields['month'] = BaseFieldDefinition::create('list_string')
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

    $fields['day'] = BaseFieldDefinition::create('string')
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

    $fields['week'] = BaseFieldDefinition::create('string')
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

    $fields['weekday'] = BaseFieldDefinition::create('list_string')
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