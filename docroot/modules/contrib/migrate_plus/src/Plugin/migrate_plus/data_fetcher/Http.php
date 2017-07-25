<?php

namespace Drupal\migrate_plus\Plugin\migrate_plus\data_fetcher;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate_plus\DataFetcherPluginBase;
use GuzzleHttp\Exception\RequestException;

/**
 * Retrieve data over an HTTP connection for migration.
 *
 * @DataFetcher(
 *   id = "http",
 *   title = @Translation("HTTP")
 * )
 */
class Http extends DataFetcherPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The HTTP Client
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * The request headers.
   *
   * @var array
   */
  protected $headers = [];

  /**
   * The data retrieval client.
   *
   * @var \Drupal\migrate_plus\AuthenticationPluginInterface
   */
  protected $authenticationPlugin;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->httpClient = \Drupal::httpClient();
  }

  /**
   * Returns the initialized authentication plugin.
   *
   * @return \Drupal\migrate_plus\AuthenticationPluginInterface
   *   The authentication plugin.
   */
  public function getAuthenticationPlugin() {
    if (!isset($this->authenticationPlugin)) {
      $this->authenticationPlugin = \Drupal::service('plugin.manager.migrate_plus.authentication')->createInstance($this->configuration['authentication']['plugin'], $this->configuration['authentication']);
    }
    return $this->authenticationPlugin;
  }

  /**
   * {@inheritdoc}
   */
  public function setRequestHeaders(array $headers) {
    $this->headers = $headers;
  }

  /**
   * {@inheritdoc}
   */
  public function getRequestHeaders() {
    return !empty($this->headers) ? $this->headers : [];
  }

  /**
   * {@inheritdoc}
   */
  public function getResponse($url) {
    try {
      $options = ['headers' => $this->getRequestHeaders()];
      if (!empty($this->configuration['authentication'])) {
        $options = array_merge($options, $this->getAuthenticationPlugin()->getAuthenticationOptions());
      }
      $response = $this->httpClient->get($url, $options);
      if (empty($response)) {
        throw new MigrateException('No response at ' . $url . '.');
      }
    }
    catch (RequestException $e) {
      throw new MigrateException('Error message: ' . $e->getMessage() . ' at ' . $url .'.');
    }
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function getResponseContent($url) {
    $response = $this->getResponse($url);
    return $response->getBody();
  }

}
