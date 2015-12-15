<?php

/**
 * @file
 * Contains \Drupal\search_api\Utility.
 */

namespace Drupal\search_api;

use Drupal\Core\Config\ConfigImporter;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\TypedData\ComplexDataDefinitionInterface;
use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\Core\TypedData\DataReferenceInterface;
use Drupal\Core\TypedData\ListInterface;
use Drupal\Core\TypedData\TypedDataInterface;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Entity\Index;
use Drupal\search_api\Item\AdditionalField;
use Drupal\search_api\Item\Field;
use Drupal\search_api\Item\FieldInterface;
use Drupal\search_api\Item\Item;
use Drupal\search_api\Query\Query;
use Drupal\search_api\Query\QueryInterface;
use Drupal\search_api\Query\ResultSet;

/**
 * Contains utility methods for the Search API.
 *
 * @todo Maybe move some of these methods to other classes (and/or split this
 *   class into several utility classes).
 */
class Utility {

  /**
   * Static cache for the field type mapping.
   *
   * @var array
   *
   * @see getFieldTypeMapping()
   */
  protected static $fieldTypeMapping = array();

  /**
   * Static cache for the fallback data type mapping per index.
   *
   * @var array
   *
   * @see getDataTypeFallbackMapping()
   */
  protected static $dataTypeFallbackMapping = array();

  /**
   * Determines whether fields of the given type contain fulltext data.
   *
   * @param string $type
   *   The type to check.
   * @param string[] $text_types
   *   (optional) An array of types to be considered as text.
   *
   * @return bool
   *   TRUE if $type is one of the specified types, FALSE otherwise.
   */
  // @todo Currently, this is useless, but later we could also check
  //   automatically for custom types that have one of the passed types as their
  //   fallback.
  public static function isTextType($type, array $text_types = array('text')) {
    return in_array($type, $text_types);
  }

  /**
   * Retrieves the mapping for known data types to Search API's internal types.
   *
   * @return string[]
   *   An array mapping all known (and supported) Drupal data types to their
   *   corresponding Search API data types. Empty values mean that fields of
   *   that type should be ignored by the Search API.
   *
   * @see hook_search_api_field_type_mapping_alter()
   */
  public static function getFieldTypeMapping() {
    // Check the static cache first.
    if (empty(static::$fieldTypeMapping)) {
      // It's easier to write and understand this array in the form of
      // $search_api_field_type => array($data_types) and flip it below.
      $default_mapping = array(
        'text' => array(
          'field_item:string_long.string',
          'field_item:text_long.string',
          'field_item:text_with_summary.string',
          'text',
        ),
        'string' => array(
          'string',
          'email',
          'uri',
          'filter_format',
          'duration_iso8601',
        ),
        'integer' => array(
          'integer',
          'timespan',
        ),
        'decimal' => array(
          'decimal',
          'float',
        ),
        'date' => array(
          'datetime_iso8601',
          'timestamp',
        ),
        'boolean' => array(
          'boolean',
        ),
        // Types we know about but want/have to ignore.
        NULL => array(
          'language',
        ),
      );

      foreach ($default_mapping as $search_api_type => $data_types) {
        foreach ($data_types as $data_type) {
          $mapping[$data_type] = $search_api_type;
        }
      }

      // Allow other modules to intercept and define what default type they want
      // to use for their data type.
      \Drupal::moduleHandler()->alter('search_api_field_type_mapping', $mapping);

      static::$fieldTypeMapping = $mapping;
    }

    return static::$fieldTypeMapping;
  }

  /**
   * Retrieves the necessary type fallbacks for an index.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The index for which to return the type fallbacks.
   *
   * @return string[]
   *   An array containing the IDs of all custom data types that are not
   *   supported by the index's current server, mapped to their fallback types.
   */
  public static function getDataTypeFallbackMapping(IndexInterface $index) {
    // Check the static cache first.
    $index_id = $index->id();
    if (empty(static::$dataTypeFallbackMapping[$index_id])) {
      $server = NULL;
      try {
        $server = $index->getServer();
      }
      catch (SearchApiException $e) {
        // If the server isn't available, just ignore it here and return all
        // custom types.
      }
      static::$dataTypeFallbackMapping[$index_id] = array();
      /** @var \Drupal\search_api\DataType\DataTypeInterface $data_type */
      foreach (\Drupal::service('plugin.manager.search_api.data_type')->getInstances() as $type_id => $data_type) {
        // We know for sure that we do not need to fall back for the default
        // data types as they are always present and are required to be
        // supported by all backends.
        if (!$data_type->isDefault() && (!$server || !$server->supportsDataType($type_id))) {
          static::$dataTypeFallbackMapping[$index_id][$type_id] = $data_type->getFallbackType();
        }
      }
    }

    return static::$dataTypeFallbackMapping[$index_id];
  }

  /**
   * Extracts specific field values from a complex data object.
   *
   * @param \Drupal\Core\TypedData\ComplexDataInterface $item
   *   The item from which fields should be extracted.
   * @param \Drupal\search_api\Item\FieldInterface[] $fields
   *   The field objects into which data should be extracted, keyed by their
   *   property paths on $item.
   */
  public static function extractFields(ComplexDataInterface $item, array $fields) {
    // Figure out which fields are directly on the item and which need to be
    // extracted from nested items.
    $direct_fields = array();
    $nested_fields = array();
    foreach (array_keys($fields) as $key) {
      if (strpos($key, ':') !== FALSE) {
        list($direct, $nested) = explode(':', $key, 2);
        $nested_fields[$direct][$nested] = $fields[$key];
      }
      else {
        $direct_fields[] = $key;
      }
    }
    // Extract the direct fields.
    foreach ($direct_fields as $key) {
      try {
        self::extractField($item->get($key), $fields[$key]);
      }
      catch (\InvalidArgumentException $e) {
        // This can happen with properties added by processors.
        // @todo Find a cleaner solution for this.
      }
    }
    // Recurse for all nested fields.
    foreach ($nested_fields as $direct => $fields_nested) {
      try {
        $item_nested = $item->get($direct);
        if ($item_nested instanceof DataReferenceInterface) {
          $item_nested = $item_nested->getTarget();
        }
        if ($item_nested instanceof EntityInterface) {
          $item_nested = $item_nested->getTypedData();
        }
        if ($item_nested instanceof ComplexDataInterface && !$item_nested->isEmpty()) {
          self::extractFields($item_nested, $fields_nested);
        }
        elseif ($item_nested instanceof ListInterface && !$item_nested->isEmpty()) {
          foreach ($item_nested as $list_item) {
            self::extractFields($list_item, $fields_nested);
          }
        }
      }
      catch (\InvalidArgumentException $e) {
        // This can happen with properties added by processors.
        // @todo Find a cleaner solution for this.
      }
    }
  }

  /**
   * Extracts value and original type from a single piece of data.
   *
   * @param \Drupal\Core\TypedData\TypedDataInterface $data
   *   The piece of data from which to extract information.
   * @param \Drupal\search_api\Item\FieldInterface $field
   *   The field into which to put the extracted data.
   */
  public static function extractField(TypedDataInterface $data, FieldInterface $field) {
    if ($data->getDataDefinition()->isList()) {
      foreach ($data as $piece) {
        self::extractField($piece, $field);
      }
      return;
    }
    $value = $data->getValue();
    $definition = $data->getDataDefinition();
    if ($definition instanceof ComplexDataDefinitionInterface) {
      $property = $definition->getMainPropertyName();
      if (isset($value[$property])) {
        $value = $value[$property];
      }
    }
    elseif (is_array($value)) {
      $value = reset($value);
    }

    // If the data type of the field is a custom one, then the value can be
    // altered by the data type plugin.
    $data_type_manager = \Drupal::service('plugin.manager.search_api.data_type');
    if ($data_type_manager->hasDefinition($field->getType())) {
      $value = $data_type_manager->createInstance($field->getType())->getValue($value);
    }

    $field->addValue($value);
    $field->setOriginalType($definition->getDataType());
  }

  /**
   * Retrieves the server task manager.
   *
   * @return \Drupal\search_api\Task\ServerTaskManagerInterface
   *   The server task manager.
   */
  public static function getServerTaskManager() {
    return \Drupal::service('search_api.server_task_manager');
  }

  /**
   * Retrieves the index task manager.
   *
   * @return \Drupal\search_api\Task\IndexTaskManagerInterface
   *   The index task manager.
   */
  public static function getIndexTaskManager() {
    return \Drupal::service('search_api.index_task_manager');
  }

  /**
   * Processes all pending index tasks inside a batch run.
   *
   * @param array $context
   *   The current batch context.
   * @param \Drupal\Core\Config\ConfigImporter $config_importer
   *   The config importer.
   */
  public static function processIndexTasks(array &$context, ConfigImporter $config_importer) {
    $index_task_manager = static::getIndexTaskManager();

    if (!isset($context['sandbox']['indexes'])) {
      $context['sandbox']['indexes'] = array();

      $indexes = \Drupal::entityManager()
        ->getStorage('search_api_index')
        ->loadByProperties(array(
          'status' => TRUE,
        ));
      $deleted = $config_importer->getUnprocessedConfiguration('delete');

      /** @var \Drupal\search_api\IndexInterface $index */
      foreach ($indexes as $index_id => $index) {
        if (!$index_task_manager->isTrackingComplete($index) && !in_array($index->getConfigDependencyName(), $deleted)) {
          $context['sandbox']['indexes'][] = $index_id;
        }
      }
      $context['sandbox']['total'] = count($context['sandbox']['indexes']);
      if (!$context['sandbox']['total']) {
        $context['finished'] = 1;
        return;
      }
    }

    $index_id = array_shift($context['sandbox']['indexes']);
    $index = Index::load($index_id);
    $added = $index_task_manager->addItemsOnce($index);
    if ($added !== NULL) {
      array_unshift($context['sandbox']['indexes'], $index_id);
    }

    if (empty($context['sandbox']['indexes'])) {
      $context['finished'] = 1;
    }
    else {
      $finished = $context['sandbox']['total'] - count($context['sandbox']['indexes']);
      $context['finished'] = $finished / $context['sandbox']['total'];
      $args = array(
        '%index' => $index->label(),
        '@num' => $finished + 1,
        '@total' => $context['sandbox']['total'],
      );
      $context['message'] = \Drupal::translation()->translate('Tracking items for search index %index (@num of @total)', $args);
    }
  }

  /**
   * Creates a new search query object.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The index on which to search.
   * @param array $options
   *   (optional) The options to set for the query. See
   *   \Drupal\search_api\Query\QueryInterface::setOption() for a list of
   *   options that are recognized by default.
   *
   * @return \Drupal\search_api\Query\QueryInterface
   *   A search query object to use.
   *
   * @see \Drupal\search_api\Query\QueryInterface::create()
   */
  public static function createQuery(IndexInterface $index, array $options = array()) {
    $search_results_cache = \Drupal::service('search_api.results_static_cache');
    return Query::create($index, $search_results_cache, $options);
  }

  /**
   * Creates a new search result set.
   *
   * @param \Drupal\search_api\Query\QueryInterface $query
   *   The executed search query.
   *
   * @return \Drupal\search_api\Query\ResultSetInterface
   *   A search result set for the given query.
   */
  public static function createSearchResultSet(QueryInterface $query) {
    return new ResultSet($query);
  }

  /**
   * Creates a search item object.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The item's search index.
   * @param string $id
   *   The item's (combined) ID.
   * @param \Drupal\search_api\Datasource\DatasourceInterface|null $datasource
   *   (optional) The datasource of the item. If not set, it will be determined
   *   from the ID and loaded from the index if needed.
   *
   * @return \Drupal\search_api\Item\ItemInterface
   *   A search item with the given values.
   */
  public static function createItem(IndexInterface $index, $id, DatasourceInterface $datasource = NULL) {
    return new Item($index, $id, $datasource);
  }

  /**
   * Creates a search item object by wrapping an existing complex data object.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The item's search index.
   * @param \Drupal\Core\TypedData\ComplexDataInterface $original_object
   *   The original object to wrap.
   * @param string $id
   *   (optional) The item's (combined) ID. If not set, it will be determined
   *   with the \Drupal\search_api\Datasource\DatasourceInterface::getItemId()
   *   method of $datasource. In this case, $datasource must not be NULL.
   * @param \Drupal\search_api\Datasource\DatasourceInterface|null $datasource
   *   (optional) The datasource of the item. If not set, it will be determined
   *   from the ID and loaded from the index if needed.
   *
   * @return \Drupal\search_api\Item\ItemInterface
   *   A search item with the given values.
   *
   * @throws \InvalidArgumentException
   *   Thrown if both $datasource and $id are NULL.
   */
  public static function createItemFromObject(IndexInterface $index, ComplexDataInterface $original_object, $id = NULL, DatasourceInterface $datasource = NULL) {
    if (!isset($id)) {
      if (!isset($datasource)) {
        throw new \InvalidArgumentException('Need either an item ID or the datasource to create a search item from an object.');
      }
      $id = self::createCombinedId($datasource->getPluginId(), $datasource->getItemId($original_object));
    }
    $item = static::createItem($index, $id, $datasource);
    $item->setOriginalObject($original_object);
    return $item;
  }

  /**
   * Creates a new field object wrapping a field of the given index.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The index to which this field should be attached.
   * @param string $field_identifier
   *   The field identifier.
   *
   * @return \Drupal\search_api\Item\FieldInterface
   *   An object containing information about the field on the given index.
   */
  public static function createField(IndexInterface $index, $field_identifier) {
    return new Field($index, $field_identifier);
  }

  /**
   * Creates a new field object wrapping an additional field of the given index.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The index to which this field should be attached.
   * @param string $field_identifier
   *   The field identifier.
   *
   * @return \Drupal\search_api\Item\AdditionalFieldInterface
   *   An object containing information about the additional field on the given
   *   index.
   */
  public static function createAdditionalField(IndexInterface $index, $field_identifier) {
    return new AdditionalField($index, $field_identifier);
  }

  /**
   * Creates a single token for the "tokenized_text" type.
   *
   * @param string $value
   *   The word or other token value.
   * @param float $score
   *   (optional) The token's score.
   *
   * @return array
   *   An array with appropriate "value" and "score" keys set.
   */
  public static function createTextToken($value, $score = 1.0) {
    return array(
      'value' => $value,
      'score' => (float) $score,
    );
  }

  /**
   * Returns a deep copy of the input array.
   *
   * The behavior of PHP regarding arrays with references pointing to it is
   * rather weird. Therefore, this method should be used when making a copy of
   * such an array, or of an array containing references.
   *
   * This method will also omit empty array elements (i.e., elements that
   * evaluate to FALSE according to PHP's native rules).
   *
   * @param array $array
   *   The array to copy.
   *
   * @return array
   *   A deep copy of the array.
   */
  public static function deepCopy(array $array) {
    $copy = array();
    foreach ($array as $k => $v) {
      if (is_array($v)) {
        if ($v = static::deepCopy($v)) {
          $copy[$k] = $v;
        }
      }
      elseif (is_object($v)) {
        $copy[$k] = clone $v;
      }
      elseif ($v) {
        $copy[$k] = $v;
      }
    }
    return $copy;
  }

  /**
   * Creates a combined ID from a raw ID and an optional datasource prefix.
   *
   * This can be used to created an internal item ID or field identifier from a
   * datasource ID and a datasource-specific raw item ID or property path.
   *
   * @param string|null $datasource_id
   *   The ID of the datasource to which the item or field belongs. Or NULL, if
   *   the returned ID should be that for a datasource-independent field.
   * @param string $raw_id
   *   The datasource-specific raw item ID or property path of the item or
   *   field.
   *
   * @return string
   *   The combined ID, with optional datasource prefix separated by
   *   \Drupal\search_api\IndexInterface::DATASOURCE_ID_SEPARATOR.
   */
  public static function createCombinedId($datasource_id, $raw_id) {
    if (!isset($datasource_id)) {
      return $raw_id;
    }
    return $datasource_id . IndexInterface::DATASOURCE_ID_SEPARATOR . $raw_id;
  }

  /**
   * Splits an internal ID into its two parts.
   *
   * Both internal item IDs and internal field identifiers are prefixed with the
   * corresponding datasource ID. This method will split these IDs up again into
   * their two parts.
   *
   * @param string $combined_id
   *   The internal ID, with an optional datasource prefix separated with
   *   \Drupal\search_api\IndexInterface::DATASOURCE_ID_SEPARATOR from the
   *   raw item ID or property path.
   *
   * @return array
   *   A numeric array, containing the datasource ID in element 0 and the raw
   *   item ID or property path in element 1. In the case of
   *   datasource-independent fields (i.e., when there is no prefix), element 0
   *   will be NULL.
   */
  public static function splitCombinedId($combined_id) {
    $pos = strpos($combined_id, IndexInterface::DATASOURCE_ID_SEPARATOR);
    if ($pos === FALSE) {
      return array(NULL, $combined_id);
    }
    return array(substr($combined_id, 0, $pos), substr($combined_id, $pos + 1));
  }

}
