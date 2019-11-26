<?php

namespace Drupal\openy_activity_finder\Plugin\search_api\processor;

use DateTime;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;

/**
 * Adds the parts of day to the indexed data.
 *
 * @SearchApiProcessor(
 *   id = "openy_af_parts_of_day",
 *   label = @Translation("Parts of day"),
 *   description = @Translation("Translates datetime values of session to an index of day's part"),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 *   locked = false,
 *   hidden = false,
 * )
 */
class PartsOfDay extends ProcessorPluginBase {

  const PROPERTY_NAME = 'search_api_af_parts_of_day';

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource) {
      $definition = [
        'label' => $this->t('Parts of day'),
        'description' => $this->t("Translates datetime values of session to an index of day's part"),
        'type' => 'string',
        'processor_id' => $this->getPluginId(),
        'is_list' => TRUE,
      ];
      $properties[self::PROPERTY_NAME] = new ProcessorProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    $object = $item->getOriginalObject();
    $entity = $object->getValue();
    $timezone = new \DateTimeZone(\Drupal::config('system.date')->get('timezone')['default']);
    $time12pm = strtotime('12:00:00Z');
    $time5pm = strtotime('17:00:00Z');

    if ($entity->hasField('field_session_time') && $paragraphs = $entity->field_session_time->referencedEntities()) {
      $values = [];
      foreach ($paragraphs as $paragraph) {
        $_period = $paragraph->field_session_time_date->getValue()[0];
        $_from = DrupalDateTime::createFromTimestamp(strtotime($_period['value'] . 'Z'), $timezone);
        $_to = DrupalDateTime::createFromTimestamp(strtotime($_period['end_value'] . 'Z'), $timezone);
        $_from_time = strtotime($_from->format('H:i:s') . 'Z');
        $_to_time = strtotime($_to->format('H:i:s') . 'Z');
        if ($_from_time < $time12pm) {
          $values[] = 1;
        }
        if ($_from_time <= $time5pm && $_to_time >= $time12pm) {
          $values[] = 2;
        }
        if ($_to_time > $time5pm) {
          $values[] = 3;
        }
      }
      $values = array_unique($values, SORT_NUMERIC);
      $fields = $this->getFieldsHelper()
        ->filterForPropertyPath($item->getFields(), NULL, self::PROPERTY_NAME);
      foreach ($fields as $field) {
        foreach ($values as $value) {
          $field->addValue($value);
        }
      }
    }
  }

}
