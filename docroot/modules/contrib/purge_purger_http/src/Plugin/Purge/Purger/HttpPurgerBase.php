<?php

/**
 * @file
 * Contains \Drupal\purge_purger_http\Plugin\Purge\Purger\HttpPurgerBase.
 */

namespace Drupal\purge_purger_http\Plugin\Purge\Purger;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Utility\Token;
use Drupal\purge\Plugin\Purge\Purger\PurgerBase;
use Drupal\purge\Plugin\Purge\Purger\PurgerInterface;
use Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface;
use Drupal\purge_purger_http\Entity\HttpPurgerSettings;

/**
 * Abstract base class for HTTP based configurable purgers.
 */
abstract class HttpPurgerBase extends PurgerBase implements PurgerInterface {

  /**
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
   * @var \Drupal\Core\Utility\Token.
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
  function __construct(array $configuration, $plugin_id, $plugin_definition, ClientInterface $http_client, Token $token) {
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
   * Turn a PHP variable into a string with data type information for debugging.
   *
   * @param mixed $symbols
   *   Arbitrary PHP variable, preferably a associative array.
   *
   * @return string
   *   A one-line comma separated string with data types as var_dump() generates.
   */
  function exportDebuggingSymbols($symbols) {

    // Capture a string using PHPs very own var_dump() using output buffering.
    ob_start();
    var_dump($symbols);
    $symbols = ob_get_clean();

    // Clean up and reduce the output footprint for both normal and xdebug output.
    if (extension_loaded('xdebug')) {
      $symbols = trim(html_entity_decode(strip_tags($symbols)));
      $symbols = substr($symbols, strpos($symbols, "\n") + 1);
      $symbols = str_replace("  '", '', $symbols);
      $symbols = str_replace("' =>", ':', $symbols);
      $symbols = implode(', ', explode("\n", $symbols));
    }
    else {
      $symbols = strip_tags($symbols);
      $symbols = substr($symbols, strpos($symbols, "\n") + 1);
      $symbols = str_replace('  ["', '', $symbols);
      $symbols = str_replace("\"]=>\n ", ':', $symbols);
      $symbols = rtrim($symbols, "}\n");
      $symbols = implode(', ', explode("\n", $symbols));
    }

    // To reduce bandwidth and storage needs we shorten data type indicators.
    $symbols = str_replace(' string', 'S', $symbols);
    $symbols = str_replace(' int', 'I', $symbols);
    $symbols = str_replace(' float', 'F', $symbols);
    $symbols = str_replace(' boolean', 'B', $symbols);
    $symbols = str_replace(' bool', 'B', $symbols);
    $symbols = str_replace(' null', 'NLL', $symbols);
    $symbols = str_replace(' NULL', 'NLL', $symbols);
    $symbols = str_replace('length=', 'l=', $symbols);

    // Return the resulting string.
    return $symbols;
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
      '%s://%s%s',
      $this->settings->scheme,
      $this->settings->hostname,
      $this->token->replace($this->settings->path, $token_data)
    );
  }

}
