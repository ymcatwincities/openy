<?php

/**
 * @file
 * Contains Drupal\migrate_plus\Plugin\migrate_plus\data_parser\JSON.
 *
 * This parser can traverse multidimensional arrays and retrieve results
 * by locating subarrays that contain a known identifier field at a known depth.
 * It can locate id fields that are nested in the results and pull out all other
 * content that is at the same level. If that content contains additional nested
 * arrays or needs other manipulation, extend this class and massage the data further
 * in the getSourceFields() method.
 *
 * For example, a file that adheres to the JSON API might look like this:
 *
 * Source:
 * [
 *   links [
 *     self: http://example.com/this_path.json
 *   ],
 *   data [
 *     entry [
 *       id: 1
 *       value1: 'something'
 *       value2: [
 *         0: green
 *         1: blue
 *       ]
 *     ]
 *     entry [
 *       id: 2
 *       value1: 'something else'
 *       value2: [
 *         0: yellow
 *         1: purple
 *       ]
 *     ]
 *   ]
 * ]
 *
 * The resulting source fields array, using identifier = 'id' and identifierDepth = 2, would be:
 * [
 *   0 [
 *     id: 1
 *     value1: 'something'
 *     value2: [
 *       0: green
 *       1: blue
 *     ]
 *   ]
 *   1 [
 *     id: 2,
 *     value1: 'something else'
 *     value2: [
 *       0: yellow
 *       1: purple
 *     ]
 *   ]
 * ]
 *
 * In the above example, the id field and the value1 field would be transformed
 * to top-level key/value pairs, as required by Migrate. The value2 field,
 * if needed, might require further manipulation by extending this class.
 *
 * @see http://php.net/manual/en/class.recursiveiteratoriterator.php
 */

namespace Drupal\migrate_plus\Plugin\migrate_plus\data_parser;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate_plus\DataParserPluginBase;
use GuzzleHttp\Exception\RequestException;

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
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  protected function getSourceData($url) {
    $iterator = $this->getSourceIterator($url);

    // Recurse through the result array. When there is an array of items at the
    // expected depth, pull that array out as a distinct item.
    $identifierDepth = $this->itemSelector;
    $items = [];
    while ($iterator->valid()) {
      $iterator->next();
      $item = $iterator->current();
      if (is_array($item) && $iterator->getDepth() == $identifierDepth) {
        $items[] = $item;
      }
    }
    return $items;
  }

  /**
   * Get the source data for reading.
   *
   * @param string $url
   *   The URL to read the source data from.
   *
   * @return \RecursiveIteratorIterator|resource
   *
   * @throws \Drupal\migrate\MigrateException
   */
  protected function getSourceIterator($url) {
    try {
      $response = $this->getDataFetcherPlugin()->getResponseContent($url);
      // The TRUE setting means decode the response into an associative array.
      $array = json_decode($response, TRUE);

      // Return the results in a recursive iterator that
      // can traverse multidimensional arrays.
      return new \RecursiveIteratorIterator(
        new \RecursiveArrayIterator($array),
        \RecursiveIteratorIterator::SELF_FIRST);
    }
    catch (RequestException $e) {
      throw new MigrateException($e->getMessage(), $e->getCode(), $e);
    }
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
        $this->currentItem[$field_name] = $current[$selector];
      }
      $this->iterator->next();
    }
  }

}
