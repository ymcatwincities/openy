<?php

/**
 * @file
 * Contains Drupal\acquia_search\AcquiaSearchV3ApiClient.
 */

namespace Drupal\acquia_search;

use Drupal\Component\Serialization\Json;
use GuzzleHttp\Exception\RequestException;

/**
 * Class AcquiaSearchV3ApiClient.
 *
 * @package Drupal\acquia_search\
 */

class AcquiaSearchV3ApiClient {

  /**
   * The HTTP client to fetch the feed data with.
   *
   * @var \GuzzleHttp\Client $client
   */
  protected $client;

  public function __construct($host, $api_key, $http_client, $cache) {
    $this->search_v3_host = $host;
    $this->search_v3_api_key = $api_key;
    $this->httpClient = $http_client;
    $this->headers = array(
      'Content-Type' => 'application/json',
      'Accept' => 'application/json',
    );
    $this->cache = $cache;
  }

  /**
   * Helper function to fetch all search v3 indexes for given network_id.
   *
   * @param $network_id
   *   Subscription network id.
   *
   * @return array|false
   *   Response array or FALSE
   */
  public function getSearchV3Indexes($network_id) {
    $result = array();
    if ($cache = $this->cache->get('acquia_search.v3indexes')) {
      if (is_array($cache->data) && $cache->expire > time()) {
        return $cache->data;
      }
    }
    $indexes = $this->searchRequest('/index/network_id/get_all?network_id=' . $network_id);
    if (is_array($indexes)) {
      if (!empty($indexes)) {
        foreach ($indexes as $index) {
          $result[] = array(
            'balancer' => $index['host'],
            'core_id' => $index['name'],
            'version' => 'v3'
          );
        }
      }
      // Cache will be set in both cases, 1. when search v3 cores are found and
      // 2. when there are no search v3 cores but api is reachable.
      $this->cache->set('acquia_search.v3indexes', $result, time() + (24 * 60 * 60));
      return $result;
    }
    else {
      // When api is not reachable, cache it for 1 minute.
      $this->cache->set('acquia_search.v3keys', $result, time() + (60));
    }

    return FALSE;
  }

  /**
   * Helper function to fetch the search v3 index keys for
   * given core_id and network_id.
   *
   * @param $core_id
   * @param $network_id
   *
   * @return array|bool|false
   *   Search v3 index keys.
   */
  public function getKeys($core_id, $network_id) {
    if ($cache = $this->cache->get('acquia_search.v3keys')) {
      if (!empty($cache->data) && $cache->expire > time()) {
        return $cache->data;
      }
    }

    $keys = $this->searchRequest('/index/key?index_name=' . $core_id . '&network_id=' . $network_id);
    if ($keys) {
      // Cache will be set in both cases, 1. when search v3 cores are found and
      // 2. when there are no search v3 cores but api is reachable.
      $this->cache->set('acquia_search.v3keys', $keys, time() + (24 * 60 * 60));
      return $keys;
    }
    else {
      // When api is not reachable, cache it for 1 minute.
      $this->cache->set('acquia_search.v3keys', $keys, time() + (60));
    }

    return FALSE;

  }

  /**
   * Create and send a request to search controller.
   *
   * @param string $path
   *   Path to call.
   *
   * @return array|false
   *   Response array or FALSE.
   */
  public function searchRequest($path) {
    $data = array(
      'host' => $this->search_v3_host,
      'headers' => array(
        'x-api-key' => $this->search_v3_api_key,
      )
    );
    $uri = $data['host'] . $path;
    $options = array(
      'headers' => $data['headers'],
      'body' => Json::encode($data),
    );

    try {
      $response = $this->httpClient->get($uri, $options);
      if (!$response) {
        throw new \Exception('Empty Response');
      }
      $stream_size = $response->getBody()->getSize();
      $data = Json::decode($response->getBody()->read($stream_size));
      $status_code = $response->getStatusCode();

      if ($status_code < 200 || $status_code > 299) {
        \Drupal::logger('acquia search')->error("Couldn't connect to search v3 API: @message",
          ['@message' => $response->getReasonPhrase()]);
        return FALSE;
      }
      return $data;
    }
    catch (RequestException $e) {
      if ($e->getCode() == 401) {
        \Drupal::logger('acquia search')->error("Couldn't connect to search v3 API:
          Received a 401 response from the API indicating that credentials are incorrect.
          Please validate your credentials. @message", ['@message' => $e->getMessage()]);
      }
      elseif ($e->getCode() == 404) {
        \Drupal::logger('acquia search')->error("Couldn't connect to search v3 API:
          Received a 404 response from the API indicating that the api host is incorrect.
          Please validate your host. @message", ['@message' => $e->getMessage()]);
      }
      else {
        \Drupal::logger('acquia search')->error("Couldn't connect to search v3 API: Please
        validate your api host and credentials. @message", ['@message' => $e->getMessage()]);
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('acquia search')->error("Couldn't connect to search v3 API: @message",
        ['@message' => $e->getMessage()]);
    }

    return FALSE;
  }

}
