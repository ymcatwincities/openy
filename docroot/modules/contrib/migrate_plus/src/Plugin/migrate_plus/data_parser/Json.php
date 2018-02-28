<?php

namespace Drupal\migrate_plus\Plugin\migrate_plus\data_parser;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate_plus\DataParserPluginBase;

/**
 * Obtain JSON data for migration.
 *
 * @DataParser(
 *   id = "json",
 *   title = @Translation("JSON")
 * )
 */
class Json extends DataParserPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The request headers passed to the data fetcher.
   *
   * @var array
   */
  protected $headers = [];

  /**
   * Iterator over the JSON data.
   *
   * @var \Iterator
   */
  protected $iterator;

  /**
   * Retrieves the JSON data and returns it as an array.
   *
   * @param string $url
   *   URL of a JSON feed.
   *
   * @return array
   *   The selected data to be iterated.
   *
   * @throws \GuzzleHttp\Exception\RequestException
   */
  protected function getSourceData($url) {
    $response = $this->getDataFetcherPlugin()->getResponseContent($url);

    // json_decode() expects utf8 data so let's make sure it gets it.
    $utf8response = utf8_encode($response);

    // Convert objects to associative arrays.
    $source_data = json_decode($utf8response, TRUE);
    // Backwards-compatibility for depth selection.
    if (is_int($this->itemSelector)) {
      return $this->selectByDepth($source_data);
    }

    // Otherwise, we're using xpath-like selectors.
    $selectors = explode('/', trim($this->itemSelector, '/'));
    foreach ($selectors as $selector) {
      $source_data = $source_data[$selector];
    }
    return $source_data;
  }

  /**
   * Get the source data for reading.
   *
   * @param array $raw_data
   *   Raw data from the JSON feed.
   *
   * @return array
   *   Selected items at the requested depth of the JSON feed.
   */
  protected function selectByDepth(array $raw_data) {
    // Return the results in a recursive iterator that can traverse
    // multidimensional arrays.
    $iterator = new \RecursiveIteratorIterator(
      new \RecursiveArrayIterator($raw_data),
      \RecursiveIteratorIterator::SELF_FIRST);
    $items = [];
    // Backwards-compatibility - an integer item_selector is interpreted as a
    // depth. When there is an array of items at the expected depth, pull that
    // array out as a distinct item.
    $identifierDepth = $this->itemSelector;
    $iterator->rewind();
    while ($iterator->valid()) {
      $item = $iterator->current();
      if (is_array($item) && $iterator->getDepth() == $identifierDepth) {
        $items[] = $item;
      }
      $iterator->next();
    }
    return $items;
  }

  /**
   * {@inheritdoc}
   */
  protected function openSourceUrl($url) {
    // (Re)open the provided URL.
    $source_data = $this->getSourceData($url);
    $this->iterator = new \ArrayIterator($source_data);
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  protected function fetchNextRow() {
    $current = $this->iterator->current();
    if ($current) {
      foreach ($this->fieldSelectors() as $field_name => $selector) {
        $field_data = $current;
        $field_selectors = explode('/', trim($selector, '/'));
        foreach ($field_selectors as $field_selector) {
          $field_data = $field_data[$field_selector];
        }
        $this->currentItem[$field_name] = $field_data;
      }
      if (!empty($this->configuration['include_raw_data'])) {
        $this->currentItem['raw'] = $current;
      }
      $this->iterator->next();
    }
  }

}
