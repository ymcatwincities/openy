<?php

namespace Drupal\ymca_frontend\Controller;

use Drupal\Component\Utility\SortArray;
use Drupal\ymca_mappings\Entity\Mapping;

/**
 * Controller for "Locations" page.
 */
class YMCALocationsController {

  /**
   * Set page's content.
   */
  public function content() {
    $base_path = base_path();

    $location_ids = \Drupal::entityQuery('mapping')
      ->condition('type', 'location')
      ->execute();

    $map = [
      'name' => 'field_location_name',
      'address1' => 'field_location_address_1',
      'address2' => 'field_location_address_2',
      'city' => 'field_location_city',
      'state' => 'field_location_state',
      'zip' => 'field_location_zip',
      'phone' => 'field_location_phone',
      'url' => 'field_location_url',
      'tags' => 'field_location_tags',
      'latitude' => 'field_location_latitude',
      'longitude' => 'field_location_longitude',
      'icon' => 'field_location_icon',
      'geid' => 'field_groupex_id',
      'personify_brcode' => 'field_location_personify_brcode',
      'shadow' => 'field_location_map_shadow',
      'HRS Mon-Fri' => 'field_location_hrs_mon_fri',
      'HRS Sat-Sun' => 'field_location_hrs_sat_sun',
    ];

    $locations_processed = [];

    foreach ($location_ids as $id) {
      $mapping = Mapping::load($id);

      // Push locations only with YMCA and Camp tags.
      if ($mapping->field_location_tags->value == 'YMCA' || $mapping->field_location_tags->value == 'Camp') {

        foreach ($map as $map_field => $drupal_field) {
          $value = is_null($mapping->{$drupal_field}->value) ? '' : $mapping->{$drupal_field}->value;
          $location[$map_field] = $value;
        }

        $locations_processed[] = $location;
      }
    }

    // Sort locations alphabetically.
    uasort($locations_processed, array($this, 'sortLocations'));
    $locations_processed = array_values($locations_processed);

    return array(
      '#theme' => 'locations_content',
      '#locations' => array(),
      '#attached' => array(
        'library' => array(
          'ymca_frontend/locations_map',
        ),
        'drupalSettings' => array(
          'locations' => array(
            'locations' => $locations_processed,
            'bounding_box' => array(
              48.06293,
              -93.85798,
              44.70658,
              -90.437897,
            ),
            'center_point' => array(
              46.384755,
              -92.1479385,
            ),
          ),
        ),
      ),
    );
  }

  /**
   * Set Title.
   */
  public function setTitle() {
    return t('Locations');
  }

  /**
   * Sorts the location alphabetically.
   *
   * @param array $a
   *   First item for comparison.
   * @param array $b
   *   Second item for comparison.
   *
   * @return int
   *   The comparison result for uasort().
   */
  public function sortLocations(array $a, array $b) {
    return SortArray::sortByKeyString($a, $b, 'name');
  }

}
