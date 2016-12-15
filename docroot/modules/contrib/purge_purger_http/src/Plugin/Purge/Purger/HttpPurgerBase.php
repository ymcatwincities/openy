<?php

namespace Drupal\purge_purger_http\Plugin\Purge\Purger;

use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Utility\Token;
use Drupal\purge\Plugin\Purge\Purger\PurgerBase;
use Drupal\purge\Plugin\Purge\Purger\PurgerInterface;
use Drupal\purge_purger_http\Entity\HttpPurgerSettings;

/**
 * Abstract base class for HTTP based configurable purgers.
 */
abstract class HttpPurgerBase extends PurgerBase implements PurgerInterface {

  /**
   * The Guzzle HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $client;

  /**
   * The settings entity holding all configuration.
   *
   * @var \Drupal\purge_purger_http\Entity\HttpPurgerSettings
   */
  protected $settings;

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * Constructs the HTTP purger.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   An HTTP client that can perform remote requests.
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ClientInterface $http_client, Token $token) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->settings = HttpPurgerSettings::load($this->getId());
    $this->client = $http_client;
    $this->token = $token;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('http_client'),
      $container->get('token')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    HttpPurgerSettings::load($this->getId())->delete();
  }

  /**
   * {@inheritdoc}
   */
  public function getCooldownTime() {
    return $this->settings->cooldown_time;
  }

  /**
   * {@inheritdoc}
   */
  public function getIdealConditionsLimit() {
    return $this->settings->max_requests;
  }

  /**
   * Retrieve all configured headers that need to be set.
   *
   * @param array $token_data
   *   An array of keyed objects, to pass on to the token service.
   *
   * @return string[]
   *   Associative array with header values and field names in the key.
   */
  protected function getHeaders($token_data) {
    $headers = [];
    $headers['user-agent'] = 'purge_purger_http module for Drupal 8.';
    if (strlen($this->settings->body)) {
      $headers['content-type'] = $this->settings->body_content_type;
    }
    foreach ($this->settings->headers as $header) {
      // According to https://tools.ietf.org/html/rfc2616#section-4.2, header
      // names are case-insensitive. Therefore, to aid easy overrides by end
      // users, we lower all header names so that no doubles are sent.
      $headers[strtolower($header['field'])] = $this->token->replace(
        $header['value'],
        $token_data
      );
    }
    return $headers;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    if ($this->settings->name) {
      return $this->settings->name;
    }
    else {
      return parent::getLabel();
    }
  }

  /**
   * Retrieve the Guzzle connection options to set.
   *
   * @param array $token_data
   *   An array of keyed objects, to pass on to the token service.
   *
   * @return mixed[]
   *   Associative array with option/value pairs.
   */
  protected function getOptions($token_data) {
    $opt = [
      'http_errors' => $this->settings->http_errors,
      'connect_timeout' => $this->settings->connect_timeout,
      'timeout' => $this->settings->timeout,
      'headers' => $this->getHeaders($token_data),
    ];
    if (strlen($this->settings->body)) {
      $opt['body'] = $this->token->replace($this->settings->body, $token_data);
    }
    if ($this->settings->scheme === 'https') {
      $opt['verify'] = $this->settings->verify;
    }
    return $opt;
  }

  /**
   * {@inheritdoc}
   */
  public function getTimeHint() {

    // When runtime measurement is enabled, we just use the base implementation.
    if ($this->settings->runtime_measurement) {
      return parent::getTimeHint();
    }

    // Theoretically connection timeouts and general timeouts can add up, so
    // we add up our assumption of the worst possible time it takes as well.
    return $this->settings->connect_timeout + $this->settings->timeout;
  }

  /**
   * {@inheritdoc}
   */
  public function getTypes() {
    return [$this->settings->invalidationtype];
  }

  /**
   * Retrieve the URI to connect to.
   *
   * @param array $token_data
   *   An array of keyed objects, to pass on to the token service.
   *
   * @return string
   *   URL string representation.
   */
  protected function getUri($token_data) {
    return sprintf(
      '%s://%s:%s%s',
      $this->settings->scheme,
      $this->settings->hostname,
      $this->settings->port,
      $this->token->replace($this->settings->path, $token_data)
    );
  }

  /**
   * {@inheritdoc}
   */
  public function hasRuntimeMeasurement() {
    return (bool) $this->settings->runtime_measurement;
  }

}
