<?php

/**
 * @file
 * Views integration code for the Search API Solr module.
 */

use Drupal\search_api\Exception\SearchApiException;

/**
 * Implements hook_views_data_alter().
 *
 * Adds field handlers for "virtual" fields, if the index's Solr server has
 * "Retrieve results data from Solr" enabled.
 *
 * @todo Update!
 */
function _search_api_solr_views_data_alter(array &$data) {
  try {
    foreach (entity_load_multiple('search_api_index') as $index) {
      /** @var \Drupal\search_api\IndexInterface $index */
      $server = NULL;

      try {
        $server = $index->getServer();
      }
      catch (SearchApiException $e) {
        // Just ignore invalid servers and skip the index.
      }
      if (!$server || empty($server->options['retrieve_data'])) {
        return;
      }
      // Fill in base data.
      $key = 'search_api_index_' . $index->machine_name;
      $table = & $data[$key];

      try {
        $wrapper = $index->entityWrapper(NULL, FALSE);
      }
      catch (EntityMetadataWrapperException $e) {
        watchdog_exception('search_api_solr', $e, "%type while retrieving metadata for index %index: !message in %function (line %line of %file).", array('%index' => $index->name), WATCHDOG_WARNING);
        continue;
      }

      // Remember fields that aren't added by data alterations, etc. (since
      // there isn't any other way to tell them apart).
      $normal_fields = array();
      foreach ($wrapper as $key => $property) {
        $normal_fields[$key] = TRUE;
      }

      try {
        $wrapper = $index->entityWrapper(NULL);
      }
      catch (EntityMetadataWrapperException $e) {
        watchdog_exception('search_api_solr', $e, "%type while retrieving metadata for index %index: !message in %function (line %line of %file).", array('%index' => $index->name), WATCHDOG_WARNING);
        continue;
      }

      // Add field handlers for items added by data alterations, etc.
      foreach ($wrapper as $key => $property) {
        if (empty($normal_fields[$key])) {
          $info = $property->info();
          if ($info) {
            entity_views_field_definition($key, $info, $table);
          }
        }
      }
    }
  }
  catch (Exception $e) {
    watchdog_exception('search_api_views', $e);
  }
}
