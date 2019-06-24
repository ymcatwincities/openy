<?php

namespace Drupal\openy_activity_finder\Plugin\search_api\processor;

use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;

/**
 * Adds the Ages Min Max to the indexed data.
 *
 * @SearchApiProcessor(
 *   id = "openy_af_ages_min_max",
 *   label = @Translation("Ages Min Max"),
 *   description = @Translation("Creates range of integer values from 2 provided min max values."),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 *   locked = false,
 *   hidden = false,
 * )
 */
class AgesMinMax extends ProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource) {
      $definition = [
        'label' => $this->t('Ages Min Max'),
        'description' => $this->t('Creates range of integer values from 2 provided min max values.'),
        'type' => 'string',
        'processor_id' => $this->getPluginId(),
      ];
      $properties['search_api_af_ages_min_max'] = new ProcessorProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    $object = $item->getOriginalObject();
    $entity = $object->getValue();
    if ($entity->hasField('field_session_min_age') && $entity->hasField('field_session_max_age')) {
      $min_age = $entity->field_session_min_age->value;
      $max_age = $entity->field_session_max_age->value;
      if (empty($min_age)) {
        // Set min age as 0 years if min age was not set in the session.
        $max_age = 0;
      }
      if (empty($max_age)) {
        // Set max age as 100 years if max age was not set the a session.
        $max_age = 100 * 12;
      }
      if ($min_age && $max_age) {
        $fields = $this->getFieldsHelper()
          ->filterForPropertyPath($item->getFields(), NULL, 'search_api_af_ages_min_max');
        foreach ($fields as $field) {
          $field->addValue(range($min_age, $max_age));
        }
      }
    }
  }

}
