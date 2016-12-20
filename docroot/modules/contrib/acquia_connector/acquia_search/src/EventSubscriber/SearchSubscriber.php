<?php

namespace Drupal\acquia_search\EventSubscriber;

use Solarium\Core\Event\Events;
use Solarium\Core\Plugin\Plugin;
use Drupal\Component\Utility\Crypt;
use Symfony\Component\EventDispatcher\Event;
use Solarium\Exception\HttpException;
use Drupal\acquia_connector\CryptConnector;

class SearchSubscriber extends Plugin {

  protected $client;
  protected $derived_key = [];
  protected $nonce = '';
  protected $uri = '';

  public function initPlugin($client, $options) {
    $this->client = $client;
    $dispatcher = $this->client->getEventDispatcher();
    $dispatcher->addListener(Events::PRE_EXECUTE_REQUEST, array($this, 'preExecuteRequest'));
    $dispatcher->addListener(Events::POST_EXECUTE_REQUEST, array($this, 'postExecuteRequest'));
  }

  /**
   * Build Acquia Solr Search Authenticator.
   *
   * @param PreExecuteRequestEvent $event
   */
  public function preExecuteRequest($event) {
    $request = $event->getRequest();
    $request->addParam('request_id', uniqid(), TRUE);
    $endpoint = $this->client->getEndpoint();
    $this->uri = $endpoint->getBaseUri() . $request->getUri();

    $this->nonce = Crypt::randomBytesBase64(24);
    $string = $request->getRawData();
    if (!$string) {
      $parsed_url = parse_url($this->uri);
      $path = isset($parsed_url['path']) ? $parsed_url['path'] : '/';
      $query = isset($parsed_url['query']) ? '?'. $parsed_url['query'] : '';
      $string = $path . $query; // For pings only.
    }

    $cookie = $this->calculateAuthCookie($string, $this->nonce);
    $request->addHeader('Cookie: ' . $cookie);
    $request->addHeader('User-Agent: ' . 'acquia_search/'. \Drupal::config('acquia_search.settings')->get('version'));
  }

  /**
   * Validate response.
   *
   * @param PostExecuteRequestEvent $event
   */
  public function postExecuteRequest($event) {
    $response = $event->getResponse();
    if($response->getStatusCode() != 200) {
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
   * @param $response
   * @param $nonce
   * @param $url
   *
   * @return Solarium\Core\Client\Response
   *
   * @throws \Exception
   */
  protected function authenticateResponse($response, $nonce, $url) {
    $hmac = $this->extractHmac($response->getHeaders());
    if (!$this->validateResponse($hmac, $nonce, $response->getBody())) {
      throw new HttpException('Authentication of search content failed url: '. $url);
    }
    return $response;
  }

  /**
   * Look in the headers and get the hmac_digest out.
   *
   * @param $headers
   *
   * @return string hmac_digest
   */
  public function extractHmac($headers) {
    $reg = array();
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
   * @param $hmac
   * @param $nonce
   * @param $string
   * @param null $derived_key
   * @param null $env_id
   *
   * @return bool
   */
  public function validateResponse($hmac, $nonce, $string, $derived_key = NULL, $env_id = NULL) {
    if (empty($derived_key)) {
      $derived_key = $this->getDerivedKey($env_id);
    }
    return $hmac == hash_hmac('sha1', $nonce . $string, $derived_key);
  }

  /**
   * Get the derived key for the solr hmac using the information shared with acquia.com.
   *
   * @param null $env_id
   *
   * @return mixed
   */
  public function getDerivedKey($env_id = NULL) {
    if (empty($env_id)) {
      $env_id = $this->client->getEndpoint()->getKey();
    }
    if (!isset($this->derived_key[$env_id])) {
      // If we set an explicit environment, check if this needs to overridden
      // Use the default.
      $identifier = \Drupal::config('acquia_connector.settings')->get('identifier');
      $key = \Drupal::config('acquia_connector.settings')->get('key');

      // See if we need to overwrite these values
      // In any case, this is equal for all subscriptions. Also
      // even if the search sub is different, the main subscription should be
      // active.
      $derived_key_salt = $this->getDerivedKeySalt();

      // We use a salt from acquia.com in key derivation since this is a shared
      // value that we could change on the AN side if needed to force any
      // or all clients to use a new derived key.  We also use a string
      // ('solr') specific to the service, since we want each service using a
      // derived key to have a separate one.
      if (empty($derived_key_salt) || empty($key) || empty($identifier)) {
        // Expired or invalid subscription - don't continue.
        $this->derived_key[$env_id] = '';
      }
      elseif (!isset($derived_key[$env_id])) {
        $this->derived_key[$env_id] = CryptConnector::createDerivedKey($derived_key_salt, $identifier, $key);
      }
    }

    return $this->derived_key[$env_id];
  }

  /**
   * Returns the subscription's salt used to generate the derived key.
   *
   * The salt is stored in a system variable so that this module can continue
   * connecting to Acquia Search even when the subscription data is not available.
   * The most common reason for subscription data being unavailable is a failed
   * heartbeat connection to rpc.acquia.com.
   *
   * Acquia Connector versions <= 7.x-2.7 pulled the derived key salt directly
   * from the subscription data. In order to allow for seamless upgrades, this
   * function checks whether the system variable exists and sets it with the data
   * in the subscription if it doesn't.
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
   * @param $string
   * @param $nonce
   * @param null $derived_key
   * @param null $env_id
   *
   * @return string
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

}
