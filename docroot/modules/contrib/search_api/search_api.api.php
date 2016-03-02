<?php

/**
 * @file
 * Hooks provided by the Search API module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the available Search API backends.
 *
 * Modules may implement this hook to alter the information that defines Search
 * API backends. All properties that are available in
 * \Drupal\search_api\Annotation\SearchApiBackend can be altered here, with the
 * addition of the "class" and "provider" keys.
 *
 * @param array $backend_info
 *   The Search API backend info array, keyed by backend ID.
 *
 * @see \Drupal\search_api\Backend\BackendPluginBase
 */
function hook_search_api_backend_info_alter(array &$backend_info) {
  foreach ($backend_info as $id => $info) {
    $backend_info[$id]['class'] = '\Drupal\my_module\MyBackendDecorator';
    $backend_info[$id]['example_original_class'] = $info['class'];
  }
}

/**
 * Alter the available datasources.
 *
 * Modules may implement this hook to alter the information that defines
 * datasources. All properties that are available in
 * \Drupal\search_api\Annotation\SearchApiDatasource can be altered here, with
 * the addition of the "class" and "provider" keys.
 *
 * @param array $infos
 *   The datasource info array, keyed by datasource IDs.
 *
 * @see \Drupal\search_api\Datasource\DatasourcePluginBase
 */
function hook_search_api_datasource_info_alter(array &$infos) {
  // I'm a traditionalist, I want them called "nodes"!
  $infos['entity:node']['label'] = t('Node');
}


/**
 * Alter the available processors.
 *
 * Modules may implement this hook to alter the information that defines
 * processors. All properties that are available in
 * \Drupal\search_api\Annotation\SearchApiProcessor can be altered here, with
 * the addition of the "class" and "provider" keys.
 *
 * @param array $processors
 *   The processor information to be altered, keyed by processor IDs.
 *
 * @see \Drupal\search_api\Processor\ProcessorPluginBase
 */
function hook_search_api_processor_info_alter(array &$processors) {
  if (!empty($processors['example_processor'])) {
    $processors['example_processor']['class'] = '\Drupal\my_module\MuchBetterExampleProcessor';
  }
}

/**
 * Alter the available data types.
 *
 * @param array $data_type_definitions
 *   The definitions of the data type plugins.
 *
 * @see \Drupal\search_api\DataType\DataTypePluginBase
 */
function hook_search_api_data_type_info_alter(array &$data_type_definitions) {
  if (isset($data_type_definitions['text'])) {
    $data_type_definitions['text'] = t('Parsed text');
  }
}

/**
 * Alter the mapping of Drupal data types to Search API data types.
 *
 * @param array $mapping
 *   An array mapping all known (and supported) Drupal data types to their
 *   corresponding Search API data types. Empty values mean that fields of
 *   that type should be ignored by the Search API.
 *
 * @see \Drupal\search_api\Utility::getFieldTypeMapping()
 */
function hook_search_api_field_type_mapping_alter(array &$mapping) {
  $mapping['duration_iso8601'] = NULL;
  $mapping['my_new_type'] = 'string';
}

/**
 * Allows you to log or alter the items that are indexed.
 *
 * Please be aware that generally preventing the indexing of certain items is
 * deprecated. This is better done with processors, which can easily be
 * configured and only added to indexes where this behaviour is wanted.
 * If your module will use this hook to reject certain items from indexing,
 * please document this clearly to avoid confusion.
 *
 * @param \Drupal\search_api\IndexInterface $index
 *   The search index on which items will be indexed.
 * @param \Drupal\search_api\Item\ItemInterface[] $items
 *   The items that will be indexed.
 */
function hook_search_api_index_items_alter(\Drupal\search_api\IndexInterface $index, array &$items) {
  foreach ($items as $item_id => $item) {
    list(, $raw_id) = \Drupal\search_api\Utility::splitCombinedId($item->getId());
    if ($raw_id % 5 == 0) {
      unset($items[$item_id]);
    }
  }
  drupal_set_message(t('Indexing items on index %index with the following IDs: @ids', array('%index' => $index->label(), '@ids' => implode(', ', array_keys($items)))));
}

/**
 * React after items were indexed.
 *
 * @param \Drupal\search_api\IndexInterface $index
 *   The used index.
 * @param array $item_ids
 *   An array containing the successfully indexed items' IDs.
 */
function hook_search_api_items_indexed(\Drupal\search_api\IndexInterface $index, array $item_ids) {
  if ($index->isValidDatasource('entity:node')) {
    // Note that this is just an example, and would only work if there are only
    // nodes indexed in that index (and even then the printed IDs would probably
    // not be as expected).
    drupal_set_message(t('Nodes indexed: @ids.', implode(', ', $item_ids)));
  }
}

/**
 * Alter a search query before it gets executed.
 *
 * The hook is invoked after all enabled processors have preprocessed the query.
 *
 * @param \Drupal\search_api\Query\QueryInterface $query
 *   The query that will be executed.
 */
function hook_search_api_query_alter(\Drupal\search_api\Query\QueryInterface &$query) {
  // Do not run for queries with a certain tag.
  if ($query->hasTag('example_tag')) {
    return;
  }
  // Otherwise, exclude the node with ID 10 from the search results.
  $fields = $query->getIndex()->getFields();
  foreach ($query->getIndex()->getDatasources() as $datasource_id => $datasource) {
    if ($datasource->getEntityTypeId() == 'node') {
      $field = \Drupal\search_api\Utility::createCombinedId($datasource_id, 'nid');
      if (isset($fields[$field])) {
        $query->addCondition($field, 10, '<>');
      }
    }
  }
}

/**
 * Alter a search query with a specific tag before it gets executed.
 *
 * The hook is invoked after all enabled processors have preprocessed the query.
 *
 * @param \Drupal\search_api\Query\QueryInterface $query
 *   The query that will be executed.
 */
function hook_search_api_query_TAG_alter(\Drupal\search_api\Query\QueryInterface &$query) {
  // Exclude the node with ID 10 from the search results.
  $fields = $query->getIndex()->getFields();
  foreach ($query->getIndex()->getDatasources() as $datasource_id => $datasource) {
    if ($datasource->getEntityTypeId() == 'node') {
      $field = \Drupal\search_api\Utility::createCombinedId($datasource_id, 'nid');
      if (isset($fields[$field])) {
        $query->addCondition($field, 10, '<>');
      }
    }
  }
}

/**
 * Alter a search query's result set.
 *
 * The hook is invoked after all enabled processors have postprocessed the
 * results.
 *
 * @param \Drupal\search_api\Query\ResultSetInterface $results
 *   The search results to alter.
 */
function hook_search_api_results_alter(\Drupal\search_api\Query\ResultSetInterface &$results) {
  $results->setExtraData('example_hook_invoked', microtime(TRUE));
}

/**
 * Alter the result set of a search query with a specific tag.
 *
 * The hook is invoked after all enabled processors have postprocessed the
 * results.
 *
 * @param \Drupal\search_api\Query\ResultSetInterface $results
 *   The search results to alter.
 */
function hook_search_api_results_TAG_alter(\Drupal\search_api\Query\ResultSetInterface &$results) {
  $results->setExtraData('example_hook_invoked', microtime(TRUE));
}

/**
 * React when a search index was scheduled for reindexing
 *
 * @param \Drupal\search_api\IndexInterface $index
 *   The index scheduled for reindexing.
 * @param $clear
 *   Boolean indicating whether the index was also cleared.
 */
function hook_search_api_index_reindex(\Drupal\search_api\IndexInterface $index, $clear = FALSE) {
  \Drupal\Core\Database\Database::getConnection()->insert('example_search_index_reindexed')
    ->fields(array(
      'index' => $index->id(),
      'clear' => $clear,
      'update_time' => REQUEST_TIME,
    ))
    ->execute();
}

/**
 * @} End of "addtogroup hooks".
 */
