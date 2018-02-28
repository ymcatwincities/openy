<?php

namespace Drupal\migrate_plus\Plugin\migrate_plus\data_fetcher;

use Drupal\migrate\MigrateException;
use Drupal\migrate_plus\DataFetcherPluginBase;

/**
 * Retrieve data from a local path or general URL for migration.
 *
 * @DataFetcher(
 *   id = "file",
 *   title = @Translation("File")
 * )
 */
class File extends DataFetcherPluginBase {

  /**
   * {@inheritdoc}
   */
  public function setRequestHeaders(array $headers) {
    // Does nothing.
  }

  /**
   * {@inheritdoc}
   */
  public function getRequestHeaders() {
    // Does nothing.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getResponse($url) {
      $response = file_get_contents($url);
      if ($response === FALSE) {
        throw new MigrateException('file parser plugin: could not retrieve data from ' . $url);
      }
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function getResponseContent($url) {
    $response = $this->getResponse($url);
    return $response;
  }

}
