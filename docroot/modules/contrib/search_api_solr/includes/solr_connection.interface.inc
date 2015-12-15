<?php

/**
 * The interface for a Solr connection class.
 */
interface SearchApiSolrConnectionInterface {

  /**
   * Constructs a Solr connection objects.
   *
   * @param array $options
   *   An array containing construction arguments.
   */
  public function __construct(array $options);

  /**
   * Calls the /admin/ping servlet, to test the connection to the server.
   *
   * @param int|false $timeout
   *   Maximum time to wait for ping in seconds, -1 for unlimited (default 2).
   *
   * @return float|false
   *   Seconds taken to ping the server, FALSE if timeout occured.
   */
  public function ping($timeout = 2);

  /**
   * Sets whether this connection will use soft commits when comitting.
   *
   * Note that this setting only has any effect when using Solr 4.x or higher.
   *
   * @param $soft_commit
   *   TRUE if soft commits should be used, FALSE otherwise. Default is FALSE.
   */
  public function setSoftCommit($soft_commit);

  /**
   * Tells whether this connection will use soft commits when comitting.
   *
   * Note that this setting only has any effect when using Solr 4.x or higher.
   *
   * @return
   *   TRUE if soft commits will be used, FALSE otherwise.
   */
  public function getSoftCommit();

  /**
   * Set the stream context to use for requests to the Solr server.
   *
   * Must be a valid stream context as created by stream_context_create(). By
   * default, no special stream context will be used.
   *
   * @param resource|null $stream_context
   *   A valid  stream context as created by stream_context_create(). Or NULL to
   *   use the default behavior.
   */
  public function setStreamContext($stream_context);

  /**
   * Returns the stream context to use for requests to the Solr server.
   *
   * By default, no special stream context will be used and this method will
   * return NULL.
   *
   * @return resource|null
   *   A valid  stream context as created by stream_context_create(). Or NULL if
   *   the default behavior is used.
   */
  public function getStreamContext();

  /**
   * Gets information about the Solr Core.
   *
   * @return object
   *   A response object with system information.
   */
  public function getSystemInfo();

  /**
   * Get metadata about fields in the Solr/Lucene index.
   *
   * @param int $num_terms
   *   Number of 'top terms' to return.
   *
   * @return array
   *   An array of SearchApiSolrField objects.
   */
  public function getFields($num_terms = 0);

  /**
   * Gets meta-data about the index.
   *
   * @param int $num_terms
   *   Number of 'top terms' to return.
   *
   * @return object
   *   A response object filled with data from Solr's Luke.
   */
  public function getLuke($num_terms = 0);

  /**
   * Gets information about the Solr core.
   *
   * @return SimpleXMLElement
   *   A Simple XMl document.
   */
  public function getStats();

  /**
   * Gets summary information about the Solr Core.
   */
  public function getStatsSummary();

  /**
   * Clears the cached Solr data.
   */
  public function clearCache();

  /**
   * Makes a request to a servlet (a path) that's not a standard path.
   *
   * @param string $servlet
   *   A path to be added to the base Solr path. e.g. 'extract/tika'.
   * @param array $params
   *   Any request parameters when constructing the URL.
   * @param array $options
   *  Options to be passed to drupal_http_request().
   *
   * @return object
   *  The HTTP response object.
   *
   * @throws SearchApiException
   */
  public function makeServletRequest($servlet, array $params = array(), array $options = array());

  /**
   * Gets the base URL of the Solr server.
   *
   * @return string
   *   The base URL of the Solr server.
   */
  public function getBaseUrl();

  /**
   * Sets the base URL of the Solr server.
   *
   * @param string $url
   *   The new base URL of the Solr server.
   */
  public function setBaseUrl($url);

  /**
   * Sends a raw update request to the Solr server.
   *
   * Takes a raw post body and sends it to the update service. Post body should
   * be a complete and well-formed XML document.
   *
   * @param string $rawPost
   *   The XML document to send to the Solr server's update service.
   * @param int|false $timeout
   *   (optional) Maximum expected duration (in seconds). Defaults to not timing
   *   out.
   *
   * @return object
   *   A response object.
   *
   * @throws SearchApiException
   *   If an error occurs during the service call
   */
  public function update($rawPost, $timeout = FALSE);

  /**
   * Adds an array of Solr Documents to the index all at once
   *
   * @param array $documents
   *   Should be an array of ApacheSolrDocument instances
   * @param bool $overwrite
   *   (optional) Set whether existing documents with the same IDs should be
   *   overwritten. Defaults to TRUE.
   * @param bool $commitWithin
   *   (optional) The time in which the indexed documents should be committed to
   *   the index, in milliseconds. This works in addition to the Solr server's
   *   auto commit settings. Defaults to no additional handling.
   *
   * @return object
   *   A response object.
   *
   * @throws SearchApiException
   *   If an error occurs during the service call.
   */
  public function addDocuments(array $documents, $overwrite = NULL, $commitWithin = NULL);

  /**
   * Sends a commit command to the Solr server.
   *
   * Will be synchronous unless $waitSearcher is set to FALSE.
   *
   * @param bool $waitSearcher
   *   (optional) Wait until a new searcher is opened and registered as the main
   *   query searcher, making the changes visible. Defaults to true.
   * @param int|false $timeout
   *   Seconds to wait until timing out with an exception. Defaults to an hour.
   *
   * @return object
   *   A response object.
   *
   * @throws SearchApiException
   *   If an error occurs during the service call.
   */
  public function commit($waitSearcher = TRUE, $timeout = 3600);

  /**
   * Sends a delete request based on a document ID.
   *
   * @param string $id
   *   The ID of the document which should be deleted. Expected to be UTF-8
   *   encoded.
   * @param int|false $timeout
   *   Seconds to wait until timing out with an exception. Defaults to an hour.
   *
   * @return object
   *   A response object.
   *
   * @throws SearchApiException
   *   If an error occurs during the service call.
   */
  public function deleteById($id, $timeout = 3600);

  /**
   * Sends a delete request for several documents, based on the document IDs.
   *
   * @param array $id
   *   The IDs of the documents which should be deleted. Expected to be UTF-8
   *   encoded.
   * @param int|false $timeout
   *   Seconds to wait until timing out with an exception. Defaults to an hour.
   *
   * @return object
   *   A response object.
   *
   * @throws SearchApiException
   *   If an error occurs during the service call.
   */
  public function deleteByMultipleIds(array $ids, $timeout = 3600);

  /**
   * Sends a delete request for all documents that match the given Solr query.
   *
   * @param string $rawQuery
   *   The query whose results should be deleted. Expected to be UTF-8 encoded.
   * @param int|false $timeout
   *   Seconds to wait until timing out with an exception. Defaults to an hour.
   *
   * @return object
   *   A response object.
   *
   * @throws SearchApiException
   *   If an error occurs during the service call.
   */
  public function deleteByQuery($rawQuery, $timeout = 3600);

  /**
   * Sends an optimize command to the Solr server.
   *
   * Will be synchronous unless $waitSearcher is set to FALSE.
   *
   * @param bool $waitSearcher
   *   (optional) Wait until a new searcher is opened and registered as the main
   *   query searcher, making the changes visible. Defaults to true.
   * @param int|false $timeout
   *   Seconds to wait until timing out with an exception. Defaults to an hour.
   *
   * @return object
   *   A response object.
   *
   * @throws SearchApiException
   *   If an error occurs during the service call.
   */
  public function optimize($waitSearcher = TRUE, $timeout = 3600);

  /**
   * Executes a search on the Solr server.
   *
   * @param string|null $query
   *   (optional) The raw query string. Defaults to an empty query.
   * @param array $params
   *   (optional) Key / value pairs for other query parameters (see Solr
   *   documentation). Use arrays for parameter keys used more than once (e.g.,
   *   facet.field).
   * @param string $method
   *   The HTTP method to use. Must be either "GET", "POST" or "AUTO". Defaults
   *   to "GET".
   *
   * @return object
   *   A response object.
   *
   * @throws SearchApiException
   *   If an error occurs during the service call.
   */
  public function search($query = NULL, array $params = array(), $method = 'GET');

  /**
   * Escapes special characters from a Solr query.
   *
   * A complete list of special characters in Solr queries can be viewed at
   * http://lucene.apache.org/java/docs/queryparsersyntax.html#Escaping%20Special%20Characters
   *
   * @param string $value
   *   The string to escape.
   * @param string $version
   *   An integer representing major solr version release.
   *
   * @return string
   *   An escaped string suitable for passing to Solr.
   */
  public static function escape($value, $version = 0);

  /**
   * Escapes a string that should be included in a Solr phrase.
   *
   * In contrast to escape(), this only escapes '"' and '\'.
   *
   * @param string $value
   *   The string to escape.
   *
   * @return string
   *   An escaped string suitable for passing to Solr.
   */
  public static function escapePhrase($value);

  /**
   * Converts a string to a Solr phrase.
   *
   * @param string $value
   *   The string to convert to a phrase.
   *
   * @return string
   *   A phrase string suitable for passing to Solr.
   */
  public static function phrase($value);

  /**
   * Escapes a Search API field name for passing to Solr.
   *
   * Since field names can only contain one special character, ":", there is no
   * need to use the complete escape() method.
   *
   * @param string $value
   *   The field name to escape.
   *
   * @return string
   *   An escaped string suitable for passing to Solr.
   */
  public static function escapeFieldName($value);

  /**
   * Gets the current solr version.
   *
   * @return int
   *   1, 3 or 4. Does not give a more detailed version, for that you need to
   *   use getSystemInfo().
   */
  public function getSolrVersion();

}
