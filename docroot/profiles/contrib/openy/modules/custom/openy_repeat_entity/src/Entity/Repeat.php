<?php
namespace Drupal\openy_repeat_entity\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Datetime\DateHelper;

/**
 * Defines the repeat entity class.
 *
 * @ingroup openy_repeat_entity
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
  public function getTimestamp() {
    return $this->get('start')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getTimestampTo() {
    return $this->get('end')->value;
  }


  /**
   * Retrieves Repeat Event location node.
   *
   * @return mixed
   *   Location node;
   */
  public function getLocation() {
    return $this
      ->get('location')
      ->first()
      ->get('entity')
      ->getTarget()
      ->getValue();
  }

  /**
   * Retrieves Repeat Event session node.
   *
   * @return mixed
   *   Session node;
   */
  public function getSession() {
    return $this
      ->get('session')
      ->first()
      ->get('entity')
      ->getTarget()
      ->getValue();
  }

  /**
   * Get string value of date range for session instance, in reduced duplicate.
   *
   * @return string
   *   Format M j, Y; M j - j, Y; M j - M j, Y; OR M j, Y - M j, Y; that is Short month day
   *   of month, year; removing duplicates when year, month, or day of month
   *   repeat.
   */
  public function getFormattedDateRangeDate() {
    // Load session instance date range date values.
    $timestamp = $this->get('start')->value;
    $year_start = date('Y', $timestamp);
    $month_day_start = date('M j', $timestamp);
    $timestamp_to = $this->get('end')->value;
    $year_to = date('Y', $timestamp_to);
    $month_day_to = date('M j', $timestamp_to);

    // Format date range to eliminate repeated values.
    if ($year_start == $year_to) {
      if (date('M', $timestamp) == date('M', $timestamp_to)) {
        if ($month_day_start == $month_day_to) {
          // M j, Y.
          $formatted_date = "$month_day_start, $year_start";
        }
        else {
          // M j - j, Y.
          $day_to = date('j', $timestamp_to);
          $formatted_date =  "$month_day_start - $day_to, $year_start";
        }
      }
      else {
        // M j - M j, Y.
        $formatted_date =  "$month_day_start - $month_day_to, $year_start";
      }
    }
    else {
      // M j, Y - M j, Y.
      $formatted_date = $month_day_start . ", $year_start - " . $month_day_to . ", $year_to";
    }

    return $formatted_date;
  }


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

    $fields['class'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Class'))
      ->setDescription(t('Reference to the Class.'))
      ->setSetting('target_type', 'node')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'entity_reference_label',
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete'
      ]);

    $fields['location'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Location'))
      ->setDescription(t('Reference to the Location.'))
      ->setSetting('target_type', 'node')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'entity_reference_label',
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete'
      ]);

    $fields['room'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Room'))
      ->setDescription(t('What room class is at.'));

    $fields['instructor'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Instructor'))
      ->setDescription(t('Instructor of the class.'));

    $fields['category'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Category'))
      ->setDescription(t('Category of a session.'));

    $fields['min_age'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Min Age'))
      ->setDescription(t('Minimum age.'));

    $fields['max_age'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Max Age'))
      ->setDescription(t('Maximum age.'));

    $fields['duration'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Duration'))
      ->setDescription(t('Duration of a session.'));

    $fields['register_url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Register URL'))
      ->setDescription(t('URL of a register link.'));

    $fields['register_text'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Register text'))
      ->setDescription(t('Text of a register link.'));

    $fields['productid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Product ID'))
      ->setDescription(t('Unique ID for the product from external or internal system.'))
      ->setReadOnly(FALSE);
    $fields['availability'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Availability counter'))
      ->setDescription(t('Number of products available to book or purchase.'))
      ->setReadOnly(FALSE)
      ->setDefaultValue(0);
    $fields['program_category'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Program Category'))
      ->setDescription(t('Reference to the Program Category.'))
      ->setSetting('target_type', 'node')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'entity_reference_label',
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
      ]);

    return $fields;
  }
}