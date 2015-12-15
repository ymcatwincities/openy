<?php

/**
 * Copyright (c) 2007-2009, Conduit Internet Technologies, Inc.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 *  - Redistributions of source code must retain the above copyright notice,
 *    this list of conditions and the following disclaimer.
 *  - Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 *  - Neither the name of Conduit Internet Technologies, Inc. nor the names of
 *    its contributors may be used to endorse or promote products derived from
 *    this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @copyright Copyright 2007-2009 Conduit Internet Technologies, Inc. (http://conduit-it.com)
 * @license New BSD (http://solr-php-client.googlecode.com/svn/trunk/COPYING)
 * @version $Id: Service.php 22 2009-11-09 22:46:54Z donovan.jimenez $
 *
 * @package Apache
 * @subpackage Solr
 * @author Donovan Jimenez <djimenez@conduit-it.com>
 */

/**
 * Additional code Copyright (c) 2008-2011 by Robert Douglass, James McKinney,
 * Jacob Singh, Alejandro Garza, Peter Wolanin, and additional contributors.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or (at
 * your option) any later version.

 *
 * This program is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
 * or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License
 * for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program as the file LICENSE.txt; if not, please see
 * http://www.gnu.org/licenses/old-licenses/gpl-2.0.txt.
 */

/**
 * Represents a Solr server resource.
 *
 * Contains methods for pinging, adding, deleting, committing, optimizing and
 * searching.
 */
class SearchApiSolrConnection implements SearchApiSolrConnectionInterface {

  /**
   * Defines how NamedLists should be formatted in the output.
   *
   * This specifically affects facet counts. Valid values are 'map' (default) or
   * 'flat'.
   */
  const NAMED_LIST_FORMAT = 'map';

  /**
   * Path to the ping servlet.
   */
  const PING_SERVLET = 'admin/ping';

  /**
   * Path to the update servlet.
   */
  const UPDATE_SERVLET = 'update';

  /**
   * Path to the search servlet.
   */
  const SEARCH_SERVLET = 'select';

  /**
   * Path to the luke servlet.
   */
  const LUKE_SERVLET = 'admin/luke';

  /**
   * Path to the system servlet.
   */
  const SYSTEM_SERVLET = 'admin/system';

  /**
   * Path to the stats servlet.
   */
  const STATS_SERVLET = 'admin/stats.jsp';

  /**
   * Path to the stats servlet for Solr 4.x servers.
   */
  const STATS_SERVLET_4 = 'admin/mbeans?wt=xml&stats=true';

  /**
   * Path to the file servlet.
   */
  const FILE_SERVLET = 'admin/file';

  /**
   * The options passed when creating this connection.
   *
   * @var array
   */
  protected $options;

  /**
   * The Solr server's URL.
   *
   * @var string
   */
  protected $base_url;

  /**
   * Cached URL to the update servlet.
   *
   * @var string
   */
  protected $update_url;

  /**
   * HTTP Basic Authentication header to set for requests to the Solr server.
   *
   * @var string
   */
  protected $http_auth;

  /**
   * The stream context to use for requests to the Solr server.
   *
   * Defaults to NULL (= pass no context at all).
   *
   * @var string
   */
  protected $stream_context;

  /**
   * Cache for the metadata from admin/luke.
   *
   * Contains an array of response objects, keyed by the number of "top terms".
   *
   * @var array
   *
   * @see getLuke()
   */
  protected $luke = array();

  /**
   * Cache for information about the Solr core.
   *
   * @var SimpleXMLElement
   *
   * @see getStats()
   */
  protected $stats;

  /**
   * Cache for system information.
   *
   * @var array
   *
   * @see getSystemInfo()
   */
  protected $system_info;

  /**
   * Flag that denotes whether to use soft commits for Solr 4.x.
   *
   * Defaults to TRUE.
   *
   * @var bool
   */
  protected $soft_commit = TRUE;

  /**
   * Implements SearchApiSolrConnectionInterface::__construct().
   *
   * Valid options include:
   *   - scheme: Scheme of the base URL of the Solr server. Most probably "http"
   *     or "https". Defaults to "http".
   *   - host: The host name (or IP) of the Solr server. Defaults to
   *     "localhost".
   *   - port: The port of the Solr server. Defaults to 8983.
   *   - path: The base path to the Solr server. Defaults to "/solr/".
   *   - http_user: If both this and "http_pass" are set, will use this
   *     information to add basic HTTP authentication to all requests to the
   *     Solr server. Not set by default.
   *   - http_pass: See "http_user".
   */
  public function __construct(array $options) {
    $options += array(
      'scheme' => 'http',
      'host' => 'localhost',
      'port' => 8983,
      'path' => 'solr',
      'http_user' => NULL,
      'http_pass' => NULL,
    );
    $this->options = $options;

    $path = '/' . trim($options['path'], '/') . '/';
    $this->base_url = $options['scheme'] . '://' . $options['host'] . ':' . $options['port'] . $path;

    // Set HTTP Basic Authentication parameter, if login data was set.
    if (strlen($options['http_user']) && strlen($options['http_pass'])) {
      $this->http_auth = 'Basic ' . base64_encode($options['http_user'] . ':' . $options['http_pass']);
    }
  }

  /**
   * Implements SearchApiSolrConnectionInterface::ping().
   */
  public function ping($timeout = 2) {
    $start = microtime(TRUE);

    if ($timeout <= 0.0) {
      $timeout = -1;
    }
    $pingUrl = $this->constructUrl(self::PING_SERVLET);

    // Attempt a HEAD request to the Solr ping url.
    $options = array(
      'method' => 'HEAD',
      'timeout' => $timeout,
    );
    $response = $this->makeHttpRequest($pingUrl, $options);

    if ($response->code == 200) {
      // Add 1 µs to the ping time so we never return 0.
      return (microtime(TRUE) - $start) + 1E-6;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Implements SearchApiSolrConnectionInterface::setSoftCommit().
   */
  public function setSoftCommit($soft_commit) {
    $this->soft_commit = (bool) $soft_commit;
  }

  /**
   * Implements SearchApiSolrConnectionInterface::getSoftCommit().
   */
  public function getSoftCommit() {
    return $this->soft_commit;
  }

  /**
   * Implements SearchApiSolrConnectionInterface::setStreamContext().
   */
  public function setStreamContext($stream_context) {
    $this->stream_context = $stream_context;
  }

  /**
   * Implements SearchApiSolrConnectionInterface::getStreamContext().
   */
  public function getStreamContext() {
    return $this->stream_context;
  }

  /**
   * Computes the cache ID to use for this connection.
   *
   * @param $suffix
   *   (optional) A suffix to append to the string to make it unique.
   *
   * @return string|null
   *   The cache ID to use for this connection and usage; or NULL if no caching
   *   should take place.
   */
  protected function getCacheId($suffix = '') {
    if (!empty($this->options['server'])) {
      $cid = $this->options['server'];
      return $suffix ? "$cid:$suffix" : $cid;
    }
  }

  /**
   * Call the /admin/system servlet to retrieve system information.
   *
   * Stores the retrieved information in $system_info.
   *
   * @see getSystemInfo()
   */
  protected function setSystemInfo() {
    $cid = $this->getCacheId(__FUNCTION__);
    if ($cid) {
      $cache = cache_get($cid, 'cache_search_api_solr');
      if ($cache) {
        $this->system_info = json_decode($cache->data);
      }
    }
    // Second pass to populate the cache if necessary.
    if (empty($this->system_info)) {
      $url = $this->constructUrl(self::SYSTEM_SERVLET, array('wt' => 'json'));
      $response = $this->sendRawGet($url);
      $this->system_info = json_decode($response->data);
      if ($cid) {
        cache_set($cid, $response->data, 'cache_search_api_solr');
      }
    }
  }

  /**
   * Implements SearchApiSolrConnectionInterface::getSystemInfo().
   */
  public function getSystemInfo() {
    if (!isset($this->system_info)) {
      $this->setSystemInfo();
    }
    return $this->system_info;
  }

  /**
   * Sets $this->luke with the metadata about the index from admin/luke.
   */
  protected function setLuke($num_terms = 0) {
    if (empty($this->luke[$num_terms])) {
      $cid = $this->getCacheId(__FUNCTION__ . ":$num_terms");
      if ($cid) {
        $cache = cache_get($cid, 'cache_search_api_solr');
        if (isset($cache->data)) {
          $this->luke = $cache->data;
        }
      }
      // Second pass to populate the cache if necessary.
      if (empty($this->luke[$num_terms])) {
        $params = array(
          'numTerms' => "$num_terms",
          'wt' => 'json',
          'json.nl' => self::NAMED_LIST_FORMAT,
        );
        $url = $this->constructUrl(self::LUKE_SERVLET, $params);
        $this->luke[$num_terms] = $this->sendRawGet($url);
        if ($cid) {
          cache_set($cid, $this->luke, 'cache_search_api_solr');
        }
      }
    }
  }

  /**
   * Implements SearchApiSolrConnectionInterface::getFields().
   */
  public function getFields($num_terms = 0) {
    $fields = array();
    foreach ($this->getLuke($num_terms)->fields as $name => $info) {
      $fields[$name] = new SearchApiSolrField($info);
    }
    return $fields;
  }

  /**
   * Implements SearchApiSolrConnectionInterface::getLuke().
   */
  public function getLuke($num_terms = 0) {
    if (!isset($this->luke[$num_terms])) {
      $this->setLuke($num_terms);
    }
    return $this->luke[$num_terms];
  }

  /**
   * Implements SearchApiSolrConnectionInterface::getSolrVersion().
   */
  public function getSolrVersion() {
    // Allow for overrides by the user.
    if (!empty($this->options['solr_version'])) {
      return $this->options['solr_version'];
    }

    $system_info = $this->getSystemInfo();
    // Get our solr version number
    if (isset($system_info->lucene->{'solr-spec-version'})) {
      return $system_info->lucene->{'solr-spec-version'}[0];
    }
    return 0;
  }

  /**
   * Stores information about the Solr core in $this->stats.
   */
  protected function setStats() {
    $data = $this->getLuke();
    $solr_version = $this->getSolrVersion();
    // Only try to get stats if we have connected to the index.
    if (empty($this->stats) && isset($data->index->numDocs)) {
      $cid = $this->getCacheId(__FUNCTION__);
      if ($cid) {
        $cache = cache_get($cid, 'cache_search_api_solr');
        if (isset($cache->data)) {
          $this->stats = simplexml_load_string($cache->data);
        }
      }
      // Second pass to populate the cache if necessary.
      if (empty($this->stats)) {
        if ($solr_version >= 4) {
          $url = $this->constructUrl(self::STATS_SERVLET_4);
        }
        else {
          $url = $this->constructUrl(self::STATS_SERVLET);
        }
        $response = $this->sendRawGet($url);
        $this->stats = simplexml_load_string($response->data);
        if ($cid) {
          cache_set($cid, $response->data, 'cache_search_api_solr');
        }
      }
    }
  }

  /**
   * Implements SearchApiSolrConnectionInterface::getStats().
   */
  public function getStats() {
    if (!isset($this->stats)) {
      $this->setStats();
    }
    return $this->stats;
  }

  /**
   * Implements SearchApiSolrConnectionInterface::getStatsSummary().
   */
  public function getStatsSummary() {
    $stats = $this->getStats();
    $solr_version = $this->getSolrVersion();

    $summary = array(
     '@pending_docs' => '',
     '@autocommit_time_seconds' => '',
     '@autocommit_time' => '',
     '@deletes_by_id' => '',
     '@deletes_by_query' => '',
     '@deletes_total' => '',
     '@schema_version' => '',
     '@core_name' => '',
     '@index_size' => '',
    );

    if (!empty($stats)) {
      if ($solr_version <= 3) {
        $docs_pending_xpath = $stats->xpath('//stat[@name="docsPending"]');
        $summary['@pending_docs'] = (int) trim(current($docs_pending_xpath));
        $max_time_xpath = $stats->xpath('//stat[@name="autocommit maxTime"]');
        $max_time = (int) trim(current($max_time_xpath));
        // Convert to seconds.
        $summary['@autocommit_time_seconds'] = $max_time / 1000;
        $summary['@autocommit_time'] = format_interval($max_time / 1000);
        $deletes_id_xpath = $stats->xpath('//stat[@name="deletesById"]');
        $summary['@deletes_by_id'] = (int) trim(current($deletes_id_xpath));
        $deletes_query_xpath = $stats->xpath('//stat[@name="deletesByQuery"]');
        $summary['@deletes_by_query'] = (int) trim(current($deletes_query_xpath));
        $summary['@deletes_total'] = $summary['@deletes_by_id'] + $summary['@deletes_by_query'];
        $schema = $stats->xpath('/solr/schema[1]');
        $summary['@schema_version'] = trim($schema[0]);
        $core = $stats->xpath('/solr/core[1]');
        $summary['@core_name'] = trim($core[0]);
        $size_xpath = $stats->xpath('//stat[@name="indexSize"]');
        $summary['@index_size'] = trim(current($size_xpath));
      }
      else {
        $system_info = $this->getSystemInfo();
        $docs_pending_xpath = $stats->xpath('//lst["stats"]/long[@name="docsPending"]');
        $summary['@pending_docs'] = (int) trim(current($docs_pending_xpath));
        $max_time_xpath = $stats->xpath('//lst["stats"]/str[@name="autocommit maxTime"]');
        $max_time = (int) trim(current($max_time_xpath));
        // Convert to seconds.
        $summary['@autocommit_time_seconds'] = $max_time / 1000;
        $summary['@autocommit_time'] = format_interval($max_time / 1000);
        $deletes_id_xpath = $stats->xpath('//lst["stats"]/long[@name="deletesById"]');
        $summary['@deletes_by_id'] = (int) trim(current($deletes_id_xpath));
        $deletes_query_xpath = $stats->xpath('//lst["stats"]/long[@name="deletesByQuery"]');
        $summary['@deletes_by_query'] = (int) trim(current($deletes_query_xpath));
        $summary['@deletes_total'] = $summary['@deletes_by_id'] + $summary['@deletes_by_query'];
        $schema = $system_info->core->schema;
        $summary['@schema_version'] = $schema;
        $core = $stats->xpath('//lst["core"]/str[@name="coreName"]');
        $summary['@core_name'] = trim(current($core));
        $size_xpath = $stats->xpath('//lst["core"]/str[@name="indexSize"]');
        $summary['@index_size'] = trim(current($size_xpath));
      }
    }

    return $summary;
  }

  /**
   * Implements SearchApiSolrConnectionInterface::clearCache().
   */
  public function clearCache() {
    if ($cid = $this->getCacheId()) {
      cache_clear_all($cid, 'cache_search_api_solr', TRUE);
      cache_clear_all($cid, 'cache_search_api_solr', TRUE);
    }
    $this->luke = array();
    $this->stats = NULL;
    $this->system_info = NULL;
  }

  /**
   * Checks the reponse code and throws an exception if it's not 200.
   *
   * @param object $response
   *   A response object.
   *
   * @return object
   *   The passed response object.
   *
   * @throws SearchApiException
   *   If the object's HTTP status is not 200.
   */
  protected function checkResponse($response) {
    $code = (int) $response->code;

    if ($code != 200) {
      if ($code >= 400 && $code != 403 && $code != 404) {
        // Add details, like Solr's exception message.
        $response->status_message .= $response->data;
      }
      throw new SearchApiException('"' . $code . '" Status: ' . $response->status_message);
    }

    return $response;
  }

  /**
   * Implements SearchApiSolrConnectionInterface::makeServletRequest().
   */
  public function makeServletRequest($servlet, array $params = array(), array $options = array()) {
    // Add default params.
    $params += array(
      'wt' => 'json',
      'json.nl' => self::NAMED_LIST_FORMAT,
    );

    $url = $this->constructUrl($servlet, $params);
    $response = $this->makeHttpRequest($url, $options);

    return $this->checkResponse($response);
  }

  /**
   * Central method for making a GET operation against this Solr Server
   */
  protected function sendRawGet($url, array $options = array()) {
    $options['method'] = 'GET';
    $response = $this->makeHttpRequest($url, $options);

    return $this->checkResponse($response);
  }

  /**
   * Central method for making a POST operation against this Solr Server
   */
  protected function sendRawPost($url, array $options = array()) {
    $options['method'] = 'POST';
    // Normally we use POST to send XML documents.
    if (empty($options['headers']['Content-Type'])) {
      $options['headers']['Content-Type'] = 'text/xml; charset=UTF-8';
    }
    $response = $this->makeHttpRequest($url, $options);

    return $this->checkResponse($response);
  }

  /**
   * Sends an HTTP request to Solr.
   *
   * This is just a wrapper around drupal_http_request().
   */
  protected function makeHttpRequest($url, array $options = array()) {
    if (empty($options['method']) || $options['method'] == 'GET' || $options['method'] == 'HEAD') {
      // Make sure we are not sending a request body.
      $options['data'] = NULL;
    }
    if ($this->http_auth) {
      $options['headers']['Authorization'] = $this->http_auth;
    }
    if ($this->stream_context) {
      $options['context'] = $this->stream_context;
    }

    $result = drupal_http_request($url, $options);

    if (!isset($result->code) || $result->code < 0) {
      $result->code = 0;
      $result->status_message = 'Request failed';
      $result->protocol = 'HTTP/1.0';
    }
    // Additional information may be in the error property.
    if (isset($result->error)) {
      $result->status_message .= ': ' . check_plain($result->error);
    }

    if (!isset($result->data)) {
      $result->data = '';
      $result->response = NULL;
    }
    else {
      $response = json_decode($result->data);
      if (is_object($response)) {
        foreach ($response as $key => $value) {
          $result->$key = $value;
        }
      }
    }

    return $result;
  }

  /**
   * Implements SearchApiSolrConnectionInterface::escape().
   */
  public static function escape($value, $version = 0) {
    $replacements = array();

    $specials = array('+', '-', '&&', '||', '!', '(', ')', '{', '}', '[', ']', '^', '"', '~', '*', '?', ':', "\\");
    // Solr 4.x introduces regular expressions, making the slash also a special
    // character.
    if ($version >= 4) {
      $specials[] = '/';
    }

    foreach ($specials as $special) {
      $replacements[$special] = "\\$special";
    }

    return strtr($value, $replacements);
  }

  /**
   * Implements SearchApiSolrConnectionInterface::escapePhrase().
   */
  public static function escapePhrase($value) {
    $replacements['"'] = '\"';
    $replacements["\\"] = "\\\\";
    return strtr($value, $replacements);
  }

  /**
   * Implements SearchApiSolrConnectionInterface::phrase().
   */
  public static function phrase($value) {
    return '"' . self::escapePhrase($value) . '"';
  }

  /**
   * Implements SearchApiSolrConnectionInterface::escapeFieldName().
   */
  public static function escapeFieldName($value) {
    $value = str_replace(':', '\:', $value);
    return $value;
  }

  /**
   * Returns the HTTP URL for a certain servlet on the Solr server.
   *
   * @param $servlet
   *   A string path to a Solr request handler.
   * @param array $params
   *   Additional GET parameters to append to the URL.
   * @param $added_query_string
   *   Additional query string to append to the URL.
   *
   * @return string
   */
  protected function constructUrl($servlet, array $params = array(), $added_query_string = NULL) {
    // PHP's built in http_build_query() doesn't give us the format Solr wants.
    $query_string = $this->httpBuildQuery($params);

    if ($query_string) {
      $query_string = '?' . $query_string;
      if ($added_query_string) {
        $query_string = $query_string . '&' . $added_query_string;
      }
    }
    elseif ($added_query_string) {
      $query_string = '?' . $added_query_string;
    }

    return $this->base_url . $servlet . $query_string;
  }

  /**
   * Implements SearchApiSolrConnectionInterface::getBaseUrl().
   */
  public function getBaseUrl() {
    return $this->base_url;
  }

  /**
   * Implements SearchApiSolrConnectionInterface::setBaseUrl().
   */
  public function setBaseUrl($url) {
    $this->base_url = $url;
    $this->update_url = NULL;
  }

  /**
   * Implements SearchApiSolrConnectionInterface::update().
   */
  public function update($rawPost, $timeout = FALSE) {
    if (empty($this->update_url)) {
      // Store the URL in an instance variable since many updates may be sent
      // via a single instance of this class.
      $this->update_url = $this->constructUrl(self::UPDATE_SERVLET, array('wt' => 'json'));
    }
    $options['data'] = $rawPost;
    if ($timeout) {
      $options['timeout'] = $timeout;
    }
    return $this->sendRawPost($this->update_url, $options);
  }

  /**
   * Implements SearchApiSolrConnectionInterface::addDocuments().
   */
  public function addDocuments(array $documents, $overwrite = NULL, $commitWithin = NULL) {
    $attr = '';

    if (isset($overwrite)) {
      $attr .= ' overwrite="' . ($overwrite ? 'true"' : 'false"');
    }
    if (isset($commitWithin)) {
      $attr .= ' commitWithin="' . ((int) $commitWithin) . '"';
    }

    $rawPost = "<add$attr>";
    foreach ($documents as $document) {
      if (is_object($document) && ($document instanceof SearchApiSolrDocument)) {
        $rawPost .= $document->toXml();
      }
    }
    $rawPost .= '</add>';

    return $this->update($rawPost);
  }

  /**
   * Implements SearchApiSolrConnectionInterface::commit().
   */
  public function commit($waitSearcher = TRUE, $timeout = 3600) {
    return $this->optimizeOrCommit('commit', $waitSearcher, $timeout);
  }

  /**
   * Implements SearchApiSolrConnectionInterface::deleteById().
   */
  public function deleteById($id, $timeout = 3600) {
    return $this->deleteByMultipleIds(array($id), $timeout);
  }

  /**
   * Implements SearchApiSolrConnectionInterface::deleteByMultipleIds().
   */
  public function deleteByMultipleIds(array $ids, $timeout = 3600) {
    $rawPost = '<delete>';

    foreach ($ids as $id) {
      $rawPost .= '<id>' . htmlspecialchars($id, ENT_NOQUOTES, 'UTF-8') . '</id>';
    }
    $rawPost .= '</delete>';

    return $this->update($rawPost, $timeout);
  }

  /**
   * Implements SearchApiSolrConnectionInterface::deleteByQuery().
   */
  public function deleteByQuery($rawQuery, $timeout = 3600) {
    $rawPost = '<delete><query>' . htmlspecialchars($rawQuery, ENT_NOQUOTES, 'UTF-8') . '</query></delete>';

    return $this->update($rawPost, $timeout);
  }

  /**
   * Implements SearchApiSolrConnectionInterface::optimize().
   */
  public function optimize($waitSearcher = TRUE, $timeout = 3600) {
    return $this->optimizeOrCommit('optimize', $waitSearcher, $timeout);
  }

  /**
   * Sends an commit or optimize command to the Solr server.
   *
   * Will be synchronous unless $waitSearcher is set to FALSE.
   *
   * @param string $type
   *   Either "commit" or "optimize".
   * @param bool $waitSearcher
   *   (optional) Wait until a new searcher is opened and registered as the main
   *   query searcher, making the changes visible. Defaults to true.
   * @param int $timeout
   *   Seconds to wait until timing out with an exception. Defaults to an hour.
   *
   * @return object
   *   A response object.
   *
   * @throws SearchApiException
   *   If an error occurs during the service call.
   */
  protected function optimizeOrCommit($type, $waitSearcher = TRUE, $timeout = 3600) {
    $waitSearcher = $waitSearcher ? '' : ' waitSearcher="false"';

    if ($this->getSolrVersion() <= 3) {
      $rawPost = "<$type$waitSearcher />";
    }
    else {
      $softCommit = ($this->soft_commit) ?  ' softCommit="true"' : '';
      $rawPost = "<$type$waitSearcher$softCommit />";
    }

    $response = $this->update($rawPost, $timeout);
    $this->clearCache();

    return $response;
  }

  /**
   * Generates an URL-encoded query string.
   *
   * Works like PHP's built in http_build_query() (or drupal_http_build_query())
   * but uses rawurlencode() and no [] for repeated params, to be compatible
   * with the Java-based servers Solr runs on.
   *
   *
   * @param array $query
   *   The query parameters which should be set.
   * @param string $parent
   *   Internal use only.
   *
   * @return string
   *   A query string to append (after "?") to a URL.
   */
  protected function httpBuildQuery(array $query, $parent = '') {
    $params = array();

    foreach ($query as $key => $value) {
      $key = ($parent ? $parent : rawurlencode($key));

      // Recurse into children.
      if (is_array($value)) {
        $value = $this->httpBuildQuery($value, $key);
        if ($value) {
          $params[] = $value;
        }
      }
      // If a query parameter value is NULL, only append its key.
      elseif (!isset($value)) {
        $params[] = $key;
      }
      else {
        $params[] = $key . '=' . rawurlencode($value);
      }
    }

    return implode('&', $params);
  }

  /**
   * {@inheritdoc}
   */
  public function search($query = NULL, array $params = array(), $method = 'GET') {
    // Always use JSON. See
    // http://code.google.com/p/solr-php-client/issues/detail?id=6#c1 for
    // reasoning.
    $params['wt'] = 'json';
    // Additional default params.
    $params += array(
      'json.nl' => self::NAMED_LIST_FORMAT,
    );
    if (isset($query)) {
      $params['q'] = $query;
    }

    // Carry out some performance improvements when no search keys are given.
    if (!isset($params['q']) || !strlen($params['q'])) {
      // Without search keys, the qf parameter is useless. We also remove empty
      // search keys here. (With our normal service class, empty keys won't be
      // set, but another module using this connection class might do that.)
      unset($params['q'], $params['qf']);

      // If we have filters set (which will nearly always be the case, since we
      // have to filter by index), move them to the q.alt parameter where
      // possible.
      if (!empty($params['fq'])) {
        $qalt = array();
        foreach ($params['fq'] as $i => $fq) {
          // Tagged filters cannot be moved to q.alt.
          if ($fq[0] !== '{') {
            $qalt[] = "($fq)";
            unset($params['fq'][$i]);
          }
        }
        if ($qalt) {
          $params['q.alt'] = implode(' ', $qalt);
        }
        if (empty($params['fq'])) {
          unset($params['fq']);
        }
      }
    }

    // Build the HTTP query string. We have our own method for that since PHP's
    // built-in http_build_query() doesn't give us the format Solr wants.
    $queryString = $this->httpBuildQuery($params);

    if ($method == 'GET' || $method == 'AUTO') {
      $searchUrl = $this->constructUrl(self::SEARCH_SERVLET, array(), $queryString);
      if ($method == 'GET' || strlen($searchUrl) <= \Drupal::config('search_api_solr.settings')->get('http_get_max_length', 4000)) {
        return $this->sendRawGet($searchUrl);
      }
    }

    // Method is POST, or AUTO with a long query
    $searchUrl = $this->constructUrl(self::SEARCH_SERVLET);
    $options['data'] = $queryString;
    $options['headers']['Content-Type'] = 'application/x-www-form-urlencoded; charset=UTF-8';
    return $this->sendRawPost($searchUrl, $options);
  }

}
