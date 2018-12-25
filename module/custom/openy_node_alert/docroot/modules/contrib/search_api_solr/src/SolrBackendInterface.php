<?php

namespace Drupal\search_api_solr;

use Drupal\search_api\Backend\BackendInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Item\ItemInterface;
use Solarium\QueryType\Update\Query\Query as UpdateQuery;

/**
 * Defines an interface for Solr search backend plugins.
 *
 * It extends the generic \Drupal\search_api\Backend\BackendInterface and covers
 * additional Solr specific methods.
 */
interface SolrBackendInterface extends BackendInterface {

  /**
   * Creates a list of all indexed field names mapped to their Solr field names.
   *
   * The special fields "search_api_id" and "search_api_relevance" are also
   * included. Any Solr fields that exist on search results are mapped back to
   * to their local field names in the final result set.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The Search Api index.
   * @param bool $reset
   *   (optional) Whether to reset the static cache.
   *
   * @see SearchApiSolrBackend::search()
   */
  public function getSolrFieldNames(IndexInterface $index, $reset = FALSE);

  /**
   * Returns the Solr connector used for this backend.
   *
   * @return \Drupal\search_api_solr\SolrConnectorInterface
   *
   * @throws \Drupal\search_api\SearchApiException
   */
  public function getSolrConnector();

  /**
   * Retrieves a Solr document from an search api index item.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The search api index.
   * @param \Drupal\search_api\Item\ItemInterface $item
   *   An item to get documents for.
   *
   * @return \Solarium\QueryType\Update\Query\Document\Document
   *   A solr document.
   */
  public function getDocument(IndexInterface $index, ItemInterface $item);

  /**
   * Retrieves Solr documents from search api index items.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The search api index.
   * @param \Drupal\search_api\Item\ItemInterface[] $items
   *   An array of items to get documents for.
   * @param \Solarium\QueryType\Update\Query\Query $update_query
   *   The existing update query the documents should be added to.
   *
   * @return \Solarium\QueryType\Update\Query\Document\Document[]
   *   An array of solr documents.
   */
  public function getDocuments(IndexInterface $index, array $items, UpdateQuery $update_query = NULL);

  /**
   * Extract a file's content using tika within a solr server.
   *
   * @param string $filepath
   *   The real path of the file to be extracted.
   *
   * @return string
   *   The text extracted from the file.
   */
  public function extractContentFromFile($filepath);

}
