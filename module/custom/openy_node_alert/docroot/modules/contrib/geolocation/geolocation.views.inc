<?php

/**
 * @file
 * Provide views data for geolocation.module.
 */

use Drupal\field\FieldStorageConfigInterface;

/**
 * Implements hook_field_views_data().
 *
 * Views integration for taxonomy_term_reference fields.
 * Adds a term relationship to the default field data.
 *
 * @see field_views_field_default_views_data()
 */
function geolocation_field_views_data(FieldStorageConfigInterface $field_storage) {
  // User the geofield service to get the views data.
  return \Drupal::service('geolocation.core')->getViewsFieldData($field_storage);
}
