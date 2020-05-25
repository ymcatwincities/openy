<?php

namespace Drupal\openy_activity_finder\Plugin\search_api\processor;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;

/**
 * Adds the Weeks to the indexed data.
 *
 * @SearchApiProcessor(
 *   id = "openy_af_week",
 *   label = @Translation("Weeks"),
 *   description = @Translation("Creates weeks e.g. Week 1: June 1."),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 *   locked = false,
 *   hidden = false,
 * )
 */
class Week extends ProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource) {
      $definition = [
        'label' => $this->t('Weeks'),
        'description' => $this->t('Creates weeks e.g. Week 1: June 1.'),
        'type' => 'string',
        'processor_id' => $this->getPluginId(),
        'is_list' => FALSE,
      ];
      $properties['search_api_af_weeks'] = new ProcessorProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    $object = $item->getOriginalObject();
    $entity = $object->getValue();

    preg_match('/Camp/', $entity->getTitle(), $matches_title);
    preg_match('/Camp/', $entity->field_session_room->value, $matches_room);
    if (!empty($matches_title[0]) || !empty($matches_room[0])) {
      $dates = $entity->field_session_time->referencedEntities();
      foreach ($dates as $date) {
        if (empty($date) || empty($date->field_session_time_date->getValue())) {
          continue;
        }
        $_period = $date->field_session_time_date->getValue()[0];
        $week_start_date = DrupalDateTime::createFromTimestamp(strtotime($_period['value'] . 'Z'))->format('n-j-Y');
        // Check if date is in the list of camp weeks listed in config.
        $weeks = \Drupal::config('openy_activity_finder.settings')->get('weeks');
        preg_match('/' . $week_start_date . '/', $weeks, $matched_weeks);
      }

      if (!empty($week_start_date) && !empty($matched_weeks[0])) {
        $fields = $this->getFieldsHelper()
          ->filterForPropertyPath($item->getFields(), NULL, 'search_api_af_weeks');
        foreach ($fields as $field) {
          $field->addValue($week_start_date);
        }
      }
    }
  }

}
