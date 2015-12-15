<?php

/**
 * @file
 * Views hook implementations for the Search API module.
 */

use Drupal\search_api\Entity\Index;

/**
 * Implements hook_views_data().
 */
function search_api_views_data() {
  $data = array();
  try {
    foreach (Index::loadMultiple() as $index) {
      // Fill in base data.
      $key = 'search_api_index_' . $index->id();
      $table = &$data[$key];
      $table['table']['group'] = t('Index @name', array('@name' => $index->label()));
      $table['table']['base'] = array(
        'field' => 'search_api_id',
        'index' => $index->id(),
        'title' => t('Index @name', array('@name' => $index->label())),
        'help' => t('Use the %name search index for filtering and retrieving data.', array('%name' => $index->label())),
        'query_id' => 'search_api_query',
      );

      // @todo Add field, filter, … handlers for all fields.

      // Add handlers for special fields.
      $table['search_api_id']['title'] = t('Entity ID');
      $table['search_api_id']['help'] = t("The entity's ID");
      $table['search_api_id']['field']['id'] = 'numeric';
      $table['search_api_id']['sort']['id'] = 'search_api_sort';

      $table['search_api_datasource']['title'] = t('Datasource');
      $table['search_api_datasource']['help'] = t("The data source ID");
      $table['search_api_datasource']['field']['id'] = 'standard';
      // @todo Enable filtering on datasource.
      $table['search_api_datasource']['sort']['id'] = 'search_api_sort';

      $table['search_api_relevance']['group'] = t('Search');
      $table['search_api_relevance']['title'] = t('Relevance');
      $table['search_api_relevance']['help'] = t('The relevance of this search result with respect to the query');
      $table['search_api_relevance']['field']['type'] = 'decimal';
      $table['search_api_relevance']['field']['id'] = 'numeric';
      $table['search_api_relevance']['field']['click sortable'] = TRUE;
      $table['search_api_relevance']['sort']['id'] = 'search_api_sort';

      $table['search_api_excerpt']['group'] = t('Search');
      $table['search_api_excerpt']['title'] = t('Excerpt');
      $table['search_api_excerpt']['help'] = t('The search result excerpted to show found search terms');
      $table['search_api_excerpt']['field']['id'] = 'search_api_excerpt';

      $table['search_api_fulltext']['group'] = t('Search');
      $table['search_api_fulltext']['title'] = t('Fulltext search');
      $table['search_api_fulltext']['help'] = t('Search several or all fulltext fields at once.');
      $table['search_api_fulltext']['filter']['id'] = 'search_api_fulltext';
      $table['search_api_fulltext']['argument']['id'] = 'search_api_fulltext';

      $table['search_api_more_like_this']['group'] = t('Search');
      $table['search_api_more_like_this']['title'] = t('More like this');
      $table['search_api_more_like_this']['help'] = t('Find similar content.');
      $table['search_api_more_like_this']['argument']['id'] = 'search_api_more_like_this';

      // @todo Add an "All taxonomy terms" contextual filter (if applicable).
    }
  }
  catch (Exception $e) {
    watchdog_exception('search_api', $e);
  }
  return $data;
}

/**
 * Implements hook_views_plugins_cache_alter().
 */
function search_api_views_plugins_cache_alter(array &$plugins) {
  // Collect all base tables provided by this module.
  $bases = array();
  foreach (Index::loadMultiple() as $index) {
    $bases[] = 'search_api_index_' . $index->id();
  }
  $plugins['search_api']['base'] = $bases;
}

/**
 * Implements hook_views_plugins_row_alter().
 */
function search_api_views_plugins_row_alter(array &$plugins) {
  // Collect all base tables provided by this module.
  $bases = array();
  foreach (Index::loadMultiple() as $index) {
    $bases[] = 'search_api_index_' . $index->id();
  }
  $plugins['search_api']['base'] = $bases;
}
