<?php

/**
 * @file
 * Contains \Drupal\fullcalendar_legend\Plugin\Block\Bundle.
 */

namespace Drupal\fullcalendar_legend\Plugin\Block;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * @todo.
 *
 * @Plugin(
 *   id = "fullcalendar_legend_bundle",
 *   subject = @Translation("Fullcalendar Legend: Bundle"),
 *   module = "fullcalendar_legend"
 * )
 */
class Bundle extends FullcalendarLegendBase {

  /**
   * {@inheritdoc}
   */
  protected function buildLegend(array $fields) {
    $types = array();
    foreach ($fields as $field_name => $field) {
      foreach ($field['bundles'] as $entity_type => $bundles) {
        $bundle_info = entity_get_bundles($entity_type);
        foreach ($bundles as $bundle) {
          if (!isset($types[$bundle])) {
            $types[$bundle]['entity_type'] = $entity_type;
            $types[$bundle]['field_name'] = $field_name;
            $types[$bundle]['bundle'] = $bundle;
            $types[$bundle]['label'] = $bundle_info[$bundle]['label'];
          }
        }
      }
    }
    return $types;
  }

}
