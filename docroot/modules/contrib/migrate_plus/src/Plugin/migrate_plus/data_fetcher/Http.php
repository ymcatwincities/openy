<?php

/**
 * @file
 * Contains Drupal\migrate_plus\Plugin\migrate_plus\data_fetcher\Http.
 *
 * Uses the Guzzle HTTP Client library, which is wrapped by \Drupal::httpClient.
 *
 * @see http://docs.guzzlephp.org/
 */

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
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->httpClient = \Drupal::httpClient();
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
    return !empty($this->headers) ? $this->headers : array();
  }

  /**
   * {@inheritdoc}
   */
  public function getResponse($url) {
    try {
      $response = $this->httpClient->get($url, array(
        'headers' => $this->getRequestHeaders(),
        // Uncomment the following to debug the request.
        //'debug' => true,
      ));
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
