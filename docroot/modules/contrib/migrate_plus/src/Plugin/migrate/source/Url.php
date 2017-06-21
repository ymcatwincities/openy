<?php

namespace Drupal\migrate_plus\Plugin\migrate\source;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_plus\DataParserPluginInterface;

/**
 * Source plugin for retrieving data via URLs.
 *
 * @MigrateSource(
 *   id = "url"
 * )
 */
class Url extends SourcePluginExtension {

  /**
   * The source URLs to retrieve.
   *
   * @var array
   */
  protected $sourceUrls = [];

  /**
   * The data parser plugin.
   *
   * @var \Drupal\migrate_plus\DataParserPluginInterface
   */
  protected $dataParserPlugin;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration) {
    if (!is_array($configuration['urls'])) {
      $configuration['urls'] = [$configuration['urls']];
    }
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);

    $this->sourceUrls = $configuration['urls'];

    // Set a default Accept header.
/*    $this->headers = array_merge(['Accept' => 'application/json'],
      $configuration['headers'] ?: []);*/

    // See if this is a paged response with next links. If so, add to the source_urls array.
/*    foreach ( (array) $configuration['urls'] as $url) {
      $this->sourceUrls += $this->getNextLinks($url);
    }*/
  }

  /**
   * Return a string representing the source URLs.
   *
   * @return string
   *   Comma-separated list of URLs being imported.
   */
  public function __toString() {
    // This could cause a problem when using a lot of urls, may need to hash.
    $urls = implode(', ', $this->sourceUrls);
    return $urls;
  }

  /**
   * Returns the initialized data parser plugin.
   *
   * @return \Drupal\migrate_plus\DataParserPluginInterface
   *   The data parser plugin.
   */
  public function getDataParserPlugin() {
    if (!isset($this->dataParserPlugin)) {
      $this->dataParserPlugin = \Drupal::service('plugin.manager.migrate_plus.data_parser')->createInstance($this->configuration['data_parser_plugin'], $this->configuration);
    }
    return $this->dataParserPlugin;
  }

  /**
   * Creates and returns a filtered Iterator over the documents.
   *
   * @return \Iterator
   *   An iterator over the documents providing source rows that match the
   *   configured item_selector.
   */
  protected function initializeIterator() {
    return $this->getDataParserPlugin();
  }

  /**
   * Collect an array of next links from a paged response.
   */
/*  protected function getNextLinks($url) {
    $urls = array();
    $more = TRUE;
    while ($more == TRUE) {
      $response = $this->dataParserPlugin->getDataFetcher()->getResponse($url);
      if ($url = $this->getNextFromHeaders($response)) {
        $urls[] = $url;
      }
      elseif ($url = $this->getNextFromLinks($response)) {
        $urls[] = $url;
      }
      else {
        $more = FALSE;
      }
    }
    return $urls;
  }
*/
  /**
   * See if the next link is in a 'links' group in the response.
   *
   * @param \Psr\Http\Message\ResponseInterface $response
   */
/*  protected function getNextFromLinks(ResponseInterface $response) {
    $body = json_decode($response->getBody(), TRUE);
    if (!empty($body['links']) && array_key_exists('next', $body['links'])) {
      return $body['links']['next'];
    }
    return FALSE;
  }
*/
  /**
   * See if the next link is in the header.
   *
   * @param \Psr\Http\Message\ResponseInterface $response
   */
/*  protected function getNextFromHeaders(ResponseInterface $response) {
    $headers = $response->getHeader('Link');
    foreach ($headers as $header) {
      $matches = array();
      preg_match('/^<(.*)>; rel="next"$/', $header, $matches);
      if (!empty($matches) && !empty($matches[1])) {
        return $matches[1];
      }
    }
    return FALSE;
  }
*/
}
