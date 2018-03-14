<?php

/**
 * @file
 * Hooks provided by the Search API Solr search module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Lets modules alter the Solarium select query before executing it.
 *
 * @param \Solarium\Core\Query\QueryInterface $solarium_query
 *   The Solarium query object, as generated from the Search API query.
 * @param \Drupal\search_api\Query\QueryInterface $query
 *   The Search API query object representing the executed search query.
 */
function hook_search_api_solr_query_alter(\Solarium\Core\Query\QueryInterface $solarium_query, \Drupal\search_api\Query\QueryInterface $query) {
  if ($query->getOption('foobar')) {
    // If the Search API query has a 'foobar' option, remove all sorting options
    // from the Solarium query.
    $solarium_query->clearSorts();
  }
}

/**
 * Change the way the index's field names are mapped to Solr field names.
 *
 * @param \Drupal\search_api\IndexInterface $index
 *   The index whose field mappings are altered.
 * @param array $fields
 *   An associative array containing the index field names mapped to their Solr
 *   counterparts. The special fields 'search_api_id' and 'search_api_relevance'
 *   are also included.
 */
function hook_search_api_solr_field_mapping_alter(\Drupal\search_api\IndexInterface $index, array &$fields) {
  if (in_array('entity:node', $index->getDatasourceIds()) && isset($fields['entity:node|body'])) {
    $fields['entity:node|body'] = 'tm_entity$node|body_value';
  }
}

/**
 * Alter Solr documents before they are sent to Solr for indexing.
 *
 * @param \Solarium\QueryType\Update\Query\Document\Document[] $documents
 *   An array of \Solarium\QueryType\Update\Query\Document\Document objects
 *   ready to be indexed, generated from $items array.
 * @param \Drupal\search_api\IndexInterface $index
 *   The search index for which items are being indexed.
 * @param \Drupal\search_api\Item\ItemInterface[] $items
 *   An array of items to be indexed, keyed by their item IDs.
 */
function hook_search_api_solr_documents_alter(&$documents, \Drupal\search_api\IndexInterface $index, array $items) {
  // Adds a "foo" field with value "bar" to all documents.
  foreach ($documents as $document) {
    $document->setField('foo', 'bar');
  }
}

/**
 * Lets modules alter the search results returned from a Solr search.
 *
 * @param \Drupal\search_api\Query\ResultSetInterface $results
 *   The results array that will be returned for the search.
 * @param \Drupal\search_api\Query\QueryInterface $query
 *   The SearchApiQueryInterface object representing the executed search query.
 * @param \Solarium\QueryType\Select\Result\Result $resultset
 *   The Solarium result object.
 */
function hook_search_api_solr_search_results_alter(\Drupal\search_api\Query\ResultSetInterface $result_set, \Drupal\search_api\Query\QueryInterface $query, \Solarium\QueryType\Select\Result\Result $result) {
  $result_data = $result->getData();
  if (isset($result_data['facet_counts']['facet_fields']['custom_field'])) {
    // Do something with $result_set.
  }
}

/**
 * Provide Solr dynamic fields as Search API data types.
 *
 * This serves as a placeholder for documenting additional keys for
 * hook_search_api_data_type_info() which are recognized by this module to
 * automatically support dynamic field types from the schema.
 *
 * @return array
 *   In addition to the keys for the individual types that are defined by
 *   hook_search_api_data_type_info(), the following keys are regonized:
 *   - prefix: The Solr field name prefix to use for this type. Should match
 *     two existing dynamic fields definitions with names "{PREFIX}s_*" and
 *     "{PREFIX}m_*".
 *
 * @see hook_search_api_data_type_info()
 */
function search_api_solr_hook_search_api_data_type_info() {
  return array(
    // You can use any identifier you want here, but it makes sense to use the
    // field type name from schema.xml.
    'edge_n2_kw_text' => array(
      // Stock hook_search_api_data_type_info() info:
      'name' => t('Fulltext (w/ partial matching)'),
      'fallback' => 'text',
      // Dynamic field with name="te_*".
      'prefix' => 'te',
    ),
    'tlong' => array(
      // Stock hook_search_api_data_type_info() info:
      'name' => t('TrieLong'),
      'fallback' => 'integer',
      // Dynamic fields with name="its_*" and name="itm_*".
      'prefix' => 'it',
    ),
  );
}

/**
 * @} End of "addtogroup hooks".
 */
