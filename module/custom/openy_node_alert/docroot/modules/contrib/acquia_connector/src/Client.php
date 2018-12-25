<?php

namespace Drupal\acquia_connector;

use Drupal\acquia_connector\Helper\Storage;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Component\Serialization\Json;
use GuzzleHttp\Exception\RequestException;

/**
 * Class Client.
 *
 * @package Drupal\acquia_connector
 */
class Client {

  /**
   * The HTTP client to fetch the feed data with.
   *
   * @var \GuzzleHttp\Client $client
   */
  protected $client;

  /**
   * Request headers.
   *
   * @var array
   */
  protected $headers;

  /**
   * Acquia SPI server.
   *
   * @var string
   */
  protected $server;

  /**
   * Config Factory Interface.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * Client constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   Config Factory Interface.
   */
  public function __construct(ConfigFactoryInterface $config) {
    $this->config = $config->get('acquia_connector.settings');
    $this->server = $this->config->get('spi.server');

    $this->headers = array(
      'Content-Type' => 'application/json',
      'Accept' => 'application/json',
    );

    $this->client = \Drupal::service('http_client_factory')->fromOptions(
      [
        'verify' => (boolean) $this->config->get('spi.ssl_verify'),
        'http_errors' => FALSE,
      ]
    );
  }

  /**
   * Get account settings to use for creating request authorizations.
   *
   * @param string $email
   *   Acquia Network account email.
   * @param string $password
   *   Plain-text password for Acquia Network account. Will be hashed for
   *   communication.
   *
   * @return array|false
   *   Credentials array or FALSE.
   */
  public function getSubscriptionCredentials($email, $password) {
    $body = array('email' => $email);
    $authenticator = $this->buildAuthenticator($email, array('rpc_version' => ACQUIA_SPI_DATA_VERSION));
    $data = array(
      'body' => $body,
      'authenticator' => $authenticator,
    );

    // Don't use nspiCall() - key is not defined yet.
    $communication_setting = $this->request('POST', '/agent-api/subscription/communication', $data);
    if ($communication_setting) {
      $crypt_pass = new CryptConnector($communication_setting['algorithm'], $password, $communication_setting['hash_setting'], $communication_setting['extra_md5']);
      $pass = $crypt_pass->cryptPass();

      $body = array(
        'email' => $email,
        'pass' => $pass,
        'rpc_version' => ACQUIA_SPI_DATA_VERSION,
      );
      $authenticator = $this->buildAuthenticator($pass, array('rpc_version' => ACQUIA_SPI_DATA_VERSION));
      $data = array(
        'body' => $body,
        'authenticator' => $authenticator,
      );

      // Don't use nspiCall() - key is not defined yet.
      $response = $this->request('POST', '/agent-api/subscription/credentials', $data);
      if ($response['body']) {
        return $response['body'];
      }
    }
    return FALSE;
  }

  /**
   * Get Acquia subscription from Acquia Network.
   *
   * @param string $id
   *   Acquia Subscription ID.
   * @param string $key
   *   Acquia Subscription key.
   * @param array $body
   *   Optional.
   *
   * @return array|false
   *   Acquia Subscription array or FALSE.
   *
   * @throws \Exception
   */
  public function getSubscription($id, $key, array $body = array()) {
    $body['identifier'] = $id;
    // There is an identifier and key, so attempt communication.
    $subscription = array();
    \Drupal::state()->set('acquia_subscription_data.timestamp', REQUEST_TIME);

    // Include version number information.
    acquia_connector_load_versions();
    if (IS_ACQUIA_DRUPAL) {
      $body['version']  = ACQUIA_DRUPAL_VERSION;
      $body['series']   = ACQUIA_DRUPAL_SERIES;
      $body['branch']   = ACQUIA_DRUPAL_BRANCH;
      $body['revision'] = ACQUIA_DRUPAL_REVISION;
    }

    // Include Acquia Search for Search API module version number.
    if (\Drupal::moduleHandler()->moduleExists('acquia_search')) {
      foreach (array('acquia_search', 'search_api', 'search_api_solr') as $name) {
        $info = system_get_info('module', $name);
        // Send the version, or at least the core compatibility as a fallback.
        $body['search_version'][$name] = isset($info['version']) ? (string) $info['version'] : (string) $info['core'];
      }
    }

    try {
      $response = $this->nspiCall('/agent-api/subscription', $body);
      if (!empty($response['result']['authenticator']) && $this->validateResponse($key, $response['result'], $response['authenticator'])) {
        $subscription += $response['result']['body'];
        // Subscription activated.
        if (is_numeric($this->config->get('subscription_data')) && is_array($response['result']['body'])) {
          \Drupal::moduleHandler()->invokeAll('acquia_subscription_status', [$subscription]);
          \Drupal::configFactory()->getEditable('acquia_connector.settings')->set('subscription_data', $subscription)->save();
        }
        return $subscription;
      }
    }
    catch (ConnectorException $e) {
      drupal_set_message(t('Error occurred while retrieving Acquia subscription information. See logs for details.'), 'error');
      if ($e->isCustomized()) {
        \Drupal::logger('acquia connector')->error($e->getCustomMessage() . '. Response data: @data', array('@data' => json_encode($e->getAllCustomMessages())));
      }
      else {
        \Drupal::logger('acquia connector')->error($e->getMessage());
      }
      throw $e;
    }

    return FALSE;
  }

  /**
   * Get Acquia subscription from Acquia Network.
   *
   * @param string $id
   *   Acquia Subscription ID.
   * @param string $key
   *   Acquia Subscription key.
   * @param array $body
   *   Optional.
   *
   * @return array|false
   *   Response result or FALSE.
   */
  public function sendNspi($id, $key, array $body = array()) {
    $body['identifier'] = $id;

    try {
      $response = $this->nspiCall('/spi-api/site', $body);
      if (!empty($response['result']['authenticator']) && $this->validateResponse($key, $response['result'], $response['authenticator'])) {
        return $response['result'];
      }
    }
    catch (ConnectorException $e) {
      \Drupal::logger('acquia connector')->error('Error: ' . $e->getCustomMessage());
    }
    return FALSE;
  }

  /**
   * Get SPI definition.
   *
   * @param string $apiEndpoint
   *   API endpoint.
   *
   * @return array|bool
   *   Definition array or FALSE.
   */
  public function getDefinition($apiEndpoint) {
    try {
      return $this->request('GET', $apiEndpoint, array());
    }
    catch (ConnectorException $e) {
      \Drupal::logger('acquia connector')->error($e->getCustomMessage());
    }
    return FALSE;
  }

  /**
   * Validate the response authenticator.
   *
   * @param string $key
   *   Acquia Subscription key.
   * @param array $response
   *   Response.
   * @param array $requestAuthenticator
   *   Authenticator array.
   *
   * @return bool
   *   TRUE if valid response, FALSE otherwise.
   */
  protected function validateResponse($key, array $response, array $requestAuthenticator) {
    $responseAuthenticator = $response['authenticator'];
    if (!($requestAuthenticator['nonce'] === $responseAuthenticator['nonce'] && $requestAuthenticator['time'] < $responseAuthenticator['time'])) {
      return FALSE;
    }
    $hash = $this->hash($key, $responseAuthenticator['time'], $responseAuthenticator['nonce'], $response['body']);
    return ($hash === $responseAuthenticator['hash']);
  }

  /**
   * Create and send a request.
   *
   * @param string $method
   *   Method to call.
   * @param string $path
   *   Path to call.
   * @param array $data
   *   Data to send.
   *
   * @return array|false
   *   Response array or FALSE.
   *
   * @throws ConnectorException
   */
  protected function request($method, $path, $data) {
    $uri = $this->server . $path;
    $options = array(
      'headers' => $this->headers,
      'body' => Json::encode($data),
    );

    try {
      switch ($method) {
        case 'GET':
          $response = $this->client->get($uri);
          $status_code = $response->getStatusCode();
          $stream_size = $response->getBody()->getSize();
          $data = Json::decode($response->getBody()->read($stream_size));

          if ($status_code < 200 || $status_code > 299) {
            throw new ConnectorException($data['message'], $data['code'], $data);
          }

          return $data;

        break;

        case 'POST':
          $response = $this->client->post($uri, $options);
          $status_code = $response->getStatusCode();
          $stream_size = $response->getBody()->getSize();
          $data = Json::decode($response->getBody()->read($stream_size));

          if ($status_code < 200 || $status_code > 299) {
            throw new ConnectorException($data['message'], $data['code'], $data);
          }

          return $data;

        break;
      }
    }
    catch (RequestException $e) {
      throw new ConnectorException($e->getMessage(), $e->getCode());
    }

    return FALSE;
  }

  /**
   * Build authenticator to sign requests to the Acquia Network.
   *
   * @param string $key
   *   Secret key to use for signing the request.
   * @param array $params
   *   Optional parameters to include.
   *   'identifier' - Network Identifier.
   *
   * @return array
   *   Authenticator array.
   */
  protected function buildAuthenticator($key, $params = array()) {
    $authenticator = array();
    if (isset($params['identifier'])) {
      // Put Network ID in authenticator but do not use in hash.
      $authenticator['identifier'] = $params['identifier'];
      unset($params['identifier']);
    }
    $nonce = $this->getNonce();
    $authenticator['time'] = REQUEST_TIME;
    $authenticator['hash'] = $this->hash($key, REQUEST_TIME, $nonce, $params);
    $authenticator['nonce'] = $nonce;

    return $authenticator;
  }

  /**
   * Calculates a HMAC-SHA1 according to RFC2104.
   *
   * @param string $key
   *   Key.
   * @param int $time
   *   Timestamp.
   * @param string $nonce
   *   Nonce.
   * @param array $params
   *   Deprecated since API 3.0.
   *
   * @return string
   *   HMAC-SHA1 hash.
   *
   * @see http://www.ietf.org/rfc/rfc2104.txt
   */
  protected function hash($key, $time, $nonce, $params = array()) {
    $string = $time . ':' . $nonce;
    return CryptConnector::acquiaHash($key, $string);
  }

  /**
   * Get a random base 64 encoded string.
   *
   * @return string
   *   Random base 64 encoded string.
   */
  protected function getNonce() {
    return Crypt::hashBase64(uniqid(mt_rand(), TRUE) . Crypt::randomBytes(55));
  }

  /**
   * Prepare and send a REST request to Acquia Network with an authenticator.
   *
   * @param string $method
   *   HTTP method.
   * @param array $params
   *   Parameters to pass to the NSPI.
   * @param string $key
   *   Acquia Key or NULL.
   *
   * @return array
   *   NSPI response.
   *
   * @throws ConnectorException
   */
  public function nspiCall($method, $params, $key = NULL) {
    if (empty($key)) {
      $storage = new Storage();
      $key = $storage->getKey();
    }
    // Used in HMAC validation.
    $params['rpc_version'] = ACQUIA_SPI_DATA_VERSION;
    $ip = \Drupal::request()->server->get('SERVER_ADDR', '');
    $host = \Drupal::request()->server->get('HTTP_HOST', '');
    $ssl = \Drupal::request()->isSecure();
    $data = array(
      'authenticator' => $this->buildAuthenticator($key, $params),
      'ip' => $ip,
      'host' => $host,
      'ssl' => $ssl,
      'body' => $params,
    );
    $data['result'] = $this->request('POST', $method, $data);
    return $data;
  }

}
