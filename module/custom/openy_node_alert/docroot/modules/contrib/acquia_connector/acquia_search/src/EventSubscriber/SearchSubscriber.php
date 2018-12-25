<?php

/**
 * @file
 * Extends the Solarium plugin for the acquia search module.
 */

namespace Drupal\acquia_search\EventSubscriber;

use Drupal\acquia_connector\Helper\Storage;
use Solarium\Core\Client\Response;
use Solarium\Core\Event\Events;
use Solarium\Core\Event\preExecuteRequest;
use Solarium\Core\Event\postExecuteRequest;
use Solarium\Core\Plugin\Plugin;
use Drupal\Component\Utility\Crypt;
use Solarium\Exception\HttpException;
use Drupal\acquia_connector\CryptConnector;
use Drupal\acquia_search\AcquiaSearchV3ApiClient;

/**
 * Extends Solarium plugin: authenticate, etc.
 */
class SearchSubscriber extends Plugin {

  /**
   * Solarium client.
   *
   * @var \Solarium\Core\Client\Client;
   */
  protected $client;

  /**
   * Array of derived keys, keyed by environment id.
   *
   * @var array
   */
  protected $derivedKey = [];

  /**
   * Nonce.
   *
   * @var string
   */
  protected $nonce = '';

  /**
   * URI.
   *
   * @var string
   */
  protected $uri = '';

  /**
   * {@inheritdoc}
   */
  public function initPlugin($client, $options) {
    $this->client = $client;
    $dispatcher = $this->client->getEventDispatcher();
    $dispatcher->addListener(Events::PRE_EXECUTE_REQUEST, [$this, 'preExecuteRequest']);
    $dispatcher->addListener(Events::POST_EXECUTE_REQUEST, [$this, 'postExecuteRequest']);
  }

  /**
   * Build Acquia Solr Search Authenticator.
   *
   * @param preExecuteRequest $event
   *   PreExecuteRequest event.
   */
  public function preExecuteRequest(preExecuteRequest $event) {
    $request = $event->getRequest();
    $request->addParam('request_id', uniqid(), TRUE);
    // If we're hosted on Acquia, and have an Acquia request ID,
    // append it to the request so that we map Solr queries to Acquia search requests.
    if (isset($_ENV['HTTP_X_REQUEST_ID'])) {
      $xid = empty($_ENV['HTTP_X_REQUEST_ID']) ? '-' : $_ENV['HTTP_X_REQUEST_ID'];
      $request->addParam('x-request-id', $xid);
    }
    $endpoint = $this->client->getEndpoint();
    $this->uri = $endpoint->getBaseUri() . $request->getUri();

    $this->nonce = Crypt::randomBytesBase64(24);
    $string = $request->getRawData();
    if (!$string) {
      $parsed_url = parse_url($this->uri);
      $path = isset($parsed_url['path']) ? $parsed_url['path'] : '/';
      $query = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
      // For pings only.
      $string = $path . $query;
    }

    $cookie = $this->calculateAuthCookie($string, $this->nonce);
    $request->addHeader('Cookie: ' . $cookie);
    $request->addHeader('User-Agent: ' . 'acquia_search/' . \Drupal::config('acquia_search.settings')->get('version'));
  }

  /**
   * Validate response.
   *
   * @param postExecuteRequest $event
   *   postExecuteRequest event.
   */
  public function postExecuteRequest(postExecuteRequest $event) {
    $response = $event->getResponse();
    if ($response->getStatusCode() != 200) {
      throw new HttpException($response->getStatusMessage());
    }
    if ($event->getRequest()->getHandler() == 'admin/ping') {
      return;
    }
    $this->authenticateResponse($event->getResponse(), $this->nonce, $this->uri);
  }

  /**
   * Validate the hmac for the response body.
   *
   * @param \Solarium\Core\Client\Response $response
   *   Solarium Response.
   * @param string $nonce
   *   Nonce.
   * @param string $url
   *   Url.
   *
   * @return \Solarium\Core\Client\Response
   *   Solarium Response.
   *
   * @throws HttpException
   */
  protected function authenticateResponse(Response $response, $nonce, $url) {
    $hmac = $this->extractHmac($response->getHeaders());
    if (!$this->validateResponse($hmac, $nonce, $response->getBody())) {
      throw new HttpException('Authentication of search content failed url: ' . $url);
    }
    return $response;
  }

  /**
   * Look in the headers and get the hmac_digest out.
   *
   * @param array $headers
   *   Headers array.
   *
   * @return string
   *   Hmac_digest or empty string.
   */
  public function extractHmac($headers) {
    $reg = [];
    if (is_array($headers)) {
      foreach ($headers as $value) {
        if (stristr($value, 'pragma') && preg_match("/hmac_digest=([^;]+);/i", $value, $reg)) {
          return trim($reg[1]);
        }
      }
    }
    return '';
  }

  /**
   * Validate the authenticity of returned data using a nonce and HMAC-SHA1.
   *
   * @param string $hmac
   *   HMAC.
   * @param string $nonce
   *   Nonce.
   * @param string $string
   *   Data string.
   * @param string $derived_key
   *   Derived key.
   * @param string $env_id
   *   Environment Id.
   *
   * @return bool
   *   TRUE if response is valid.
   */
  public function validateResponse($hmac, $nonce, $string, $derived_key = NULL, $env_id = NULL) {
    if (empty($derived_key)) {
      $derived_key = $this->getDerivedKey($env_id);
    }
    return $hmac == hash_hmac('sha1', $nonce . $string, $derived_key);
  }

  /**
   * Get the derived key.
   *
   * Get the derived key for the solr hmac using the information shared with
   * acquia.com.
   *
   * @param string $env_id
   *   Environment Id.
   *
   * @return string
   *   Derived Key.
   */
  public function getDerivedKey($env_id = NULL) {
    if (empty($env_id)) {
      $env_id = $this->client->getEndpoint()->getKey();
    }

    // Get derived key for search v3 core if enabled.
    $search_v3_enabled = \Drupal::config('acquia_search.settings')->get('search_v3_enabled');
    if ($search_v3_enabled) {
      $search_v3_index = $this->getSearchV3IndexKeys();
      if ($search_v3_index) {
        $this->derivedKey[$env_id] = CryptConnector::createDerivedKey($search_v3_index['product_policies']['salt'], $search_v3_index['key'], $search_v3_index['secret_key']);
        return $this->derivedKey[$env_id];
      }
    }

    if (!isset($this->derivedKey[$env_id])) {
      $server = $this->client->getEndpoint();

      // If derived_key comes from configuration, use that.
      // @TODO: make sure the derived_key doesn't make it permanently into the DB!
      if (!empty($server->getOption('derived_key'))) {
        return $server->getOption('derived_key');
      }

      $acquia_index_id = $server->getOption('index_id');
      $storage = new Storage();
      $key = $storage->getKey();

      // See if we need to overwrite these values.
      // @todo: Implement the derived key per solr environment storage.
      // In any case, this is equal for all subscriptions. Also
      // even if the search sub is different, the main subscription should be
      // active.
      $derived_key_salt = $this->getDerivedKeySalt();

      // We use a salt from acquia.com in key derivation since this is a shared
      // value that we could change on the AN side if needed to force any
      // or all clients to use a new derived key.  We also use a string
      // ('solr') specific to the service, since we want each service using a
      // derived key to have a separate one.
      if (empty($derived_key_salt) || empty($key) || empty($acquia_index_id)) {
        // Expired or invalid subscription - don't continue.
        $this->derivedKey[$env_id] = '';
      }
      elseif (!isset($this->derivedKey[$env_id])) {
        $this->derivedKey[$env_id] = CryptConnector::createDerivedKey($derived_key_salt, $acquia_index_id, $key);
      }
    }

    return $this->derivedKey[$env_id];
  }

  /**
   * Returns the subscription's salt used to generate the derived key.
   *
   * The salt is stored in a system variable so that this module can continue
   * connecting to Acquia Search even when the subscription data is not
   * available.
   * The most common reason for subscription data being unavailable is a failed
   * heartbeat connection to rpc.acquia.com.
   *
   * Acquia Connector versions <= 7.x-2.7 pulled the derived key salt directly
   * from the subscription data. In order to allow for seamless upgrades, this
   * function checks whether the system variable exists and sets it with the
   * data in the subscription if it doesn't.
   *
   * @return string
   *   The derived key salt.
   *
   * @see http://drupal.org/node/1784114
   */
  public function getDerivedKeySalt() {
    $salt = \Drupal::config('acquia_search.settings')->get('derived_key_salt');
    if (!$salt) {
      // If the variable doesn't exist, set it using the subscription data.
      $subscription = \Drupal::config('acquia_connector.settings')->get('subscription_data');
      if (isset($subscription['derived_key_salt'])) {
        \Drupal::configFactory()->getEditable('acquia_search.settings')->set('derived_key_salt', $subscription['derived_key_salt'])->save();
        $salt = $subscription['derived_key_salt'];
      }
    }
    return $salt;
  }

  /**
   * Creates an authenticator based on a data string and HMAC-SHA1.
   *
   * @param string $string
   *   Data string.
   * @param string $nonce
   *   Nonce.
   * @param string $derived_key
   *   Derived key.
   * @param string $env_id
   *   Environment Id.
   *
   * @return string
   *   Auth cookie string.
   */
  public function calculateAuthCookie($string, $nonce, $derived_key = NULL, $env_id = NULL) {
    if (empty($derived_key)) {
      $derived_key = $this->getDerivedKey($env_id);
    }
    if (empty($derived_key)) {
      // Expired or invalid subscription - don't continue.
      return '';
    }
    else {
      $time = REQUEST_TIME;
      return 'acquia_solr_time=' . $time . '; acquia_solr_nonce=' . $nonce . '; acquia_solr_hmac=' . hash_hmac('sha1', $time . $nonce . $string, $derived_key) . ';';
    }
  }

  /**
   * Fetches the search v3 index keys.
   *
   * @return array | FALSE
   *   Search v3 index keys.
   */
  public function getSearchV3IndexKeys() {
    $core_service = acquia_search_get_core_service();
    if (!$core_service->isPreferredCoreAvailable()) {
      return;
    }
    $core = $core_service->getPreferredCore();
    // Check the core version to see if it's v2 or v3 core.
    if (empty($core['version']) || $core['version'] !== 'v3') {
      return;
    }

    $search_v3_client = acquia_search_get_v3_client();
    if (!$search_v3_client) {
      return;
    }

    $search_v3_index = $search_v3_client->getKeys($core['core_id'], $core_service->acquia_identifier);
    if (is_array($search_v3_index) && !empty($search_v3_index)) {
      return $search_v3_index;
    }

  }

}
