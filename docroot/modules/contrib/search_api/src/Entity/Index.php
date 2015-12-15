<?php

/**
 * @file
 * Contains \Drupal\search_api\Entity\Index.
 */

namespace Drupal\search_api\Entity;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\TypedData\FieldItemDataDefinition;
use Drupal\Core\TypedData\ComplexDataDefinitionInterface;
use Drupal\Core\TypedData\DataReferenceDefinitionInterface;
use Drupal\Core\TypedData\ListDataDefinitionInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Item\GenericFieldInterface;
use Drupal\search_api\Processor\ProcessorInterface;
use Drupal\search_api\Property\PropertyInterface;
use Drupal\search_api\Query\QueryInterface;
use Drupal\search_api\Query\ResultSetInterface;
use Drupal\search_api\SearchApiException;
use Drupal\search_api\ServerInterface;
use Drupal\search_api\Utility;
use Drupal\views\Views;

/**
 * Defines the search index configuration entity.
 *
 * @ConfigEntityType(
 *   id = "search_api_index",
 *   label = @Translation("Search index"),
 *   handlers = {
 *     "storage" = "Drupal\Core\Config\Entity\ConfigEntityStorage",
 *     "list_builder" = "Drupal\search_api\IndexListBuilder",
 *     "form" = {
 *       "default" = "Drupal\search_api\Form\IndexForm",
 *       "edit" = "Drupal\search_api\Form\IndexForm",
 *       "fields" = "Drupal\search_api\Form\IndexFieldsForm",
 *       "processors" = "Drupal\search_api\Form\IndexProcessorsForm",
 *       "delete" = "Drupal\search_api\Form\IndexDeleteConfirmForm",
 *       "disable" = "Drupal\search_api\Form\IndexDisableConfirmForm",
 *       "reindex" = "Drupal\search_api\Form\IndexReindexConfirmForm",
 *       "clear" = "Drupal\search_api\Form\IndexClearConfirmForm"
 *     },
 *   },
 *   admin_permission = "administer search_api",
 *   config_prefix = "index",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "status" = "status"
 *   },
 *   config_export = {
 *     "id",
 *     "name",
 *     "description",
 *     "read_only",
 *     "options",
 *     "datasources",
 *     "datasource_configs",
 *     "tracker",
 *     "tracker_config",
 *     "server",
 *   },
 *   links = {
 *     "canonical" = "/admin/config/search/search-api/index/{search_api_index}",
 *     "add-form" = "/admin/config/search/search-api/add-index",
 *     "edit-form" = "/admin/config/search/search-api/index/{search_api_index}/edit",
 *     "fields" = "/admin/config/search/search-api/index/{search_api_index}/fields",
 *     "processors" = "/admin/config/search/search-api/index/{search_api_index}/processors",
 *     "delete-form" = "/admin/config/search/search-api/index/{search_api_index}/delete",
 *     "disable" = "/admin/config/search/search-api/index/{search_api_index}/disable",
 *     "enable" = "/admin/config/search/search-api/index/{search_api_index}/enable",
 *   }
 * )
 */
class Index extends ConfigEntityBase implements IndexInterface {

  /**
   * The ID of the index.
   *
   * @var string
   */
  protected $id;

  /**
   * A name to be displayed for the index.
   *
   * @var string
   */
  protected $name;

  /**
   * A string describing the index.
   *
   * @var string
   */
  protected $description;

  /**
   * A flag indicating whether to write to this index.
   *
   * @var bool
   */
  protected $read_only = FALSE;

  /**
   * An array of options configuring this index.
   *
   * @var array
   *
   * @see getOptions()
   */
  protected $options = array();

  /**
   * The IDs of the datasources selected for this index.
   *
   * @var string[]
   */
  protected $datasources = array();

  /**
   * The configuration for the selected datasources.
   *
   * @var array
   */
  protected $datasource_configs = array();

  /**
   * The instantiated datasource plugins.
   *
   * @var \Drupal\search_api\Datasource\DatasourceInterface[]|null
   *
   * @see getDatasources()
   */
  protected $datasourcePlugins;

  /**
   * The tracker plugin ID.
   *
   * @var string
   */
  protected $tracker = 'default';

  /**
   * The tracker plugin configuration.
   *
   * @var array
   */
  protected $tracker_config = array();

  /**
   * The tracker plugin instance.
   *
   * @var \Drupal\search_api\Tracker\TrackerInterface|null
   *
   * @see getTracker()
   */
  protected $trackerPlugin;

  /**
   * The ID of the server on which data should be indexed.
   *
   * @var string
   */
  protected $server;

  /**
   * The server entity belonging to this index.
   *
   * @var \Drupal\search_api\ServerInterface
   *
   * @see getServer()
   */
  protected $serverInstance;

  /**
   * Cached properties for this index's datasources.
   *
   * @var \Drupal\Core\TypedData\DataDefinitionInterface[][][]
   *
   * @see getPropertyDefinitions()
   */
  protected $properties = array();

  /**
   * Cached fields data.
   *
   * The array contains two elements: 0 for all fields, 1 for indexed fields.
   * The elements under these keys are arrays with keys "fields" and "additional
   * fields", corresponding to return values for getFields() and
   * getAdditionalFields(), respectively.
   *
   * @var \Drupal\search_api\Item\GenericFieldInterface[][][]|null
   *
   * @see computeFields()
   * @see getFields()
   * @see getFieldsByDatasource()
   * @see getAdditionalFields()
   * @see getAdditionalFieldsByDatasource()
   */
  protected $fields;

  /**
   * Cached fields data, grouped by datasource and indexed state.
   *
   * The array is three-dimensional, with the first two keys corresponding to
   * the parameters of a getFieldsByDatasource() call and the last one being the
   * field ID.
   *
   * @var \Drupal\search_api\Item\FieldInterface[][][]|null
   *
   * @see getFieldsByDatasource()
   */
  protected $datasourceFields;

  /**
   * Cached additional fields data, grouped by datasource.
   *
   * The array is two-dimensional, with the first key corresponding to the
   * datasource ID and the second key being a field ID.
   *
   * @var \Drupal\search_api\Item\FieldInterface[][]|null
   *
   * @see getAdditionalFieldsByDatasource()
   */
  protected $datasourceAdditionalFields;

  /**
   * Cached information about fulltext fields in the index.
   *
   * @var string[][]|null
   *
   * @see getFulltextFields()
   */
  protected $fulltextFields;

  /**
   * Cached information about the processors available for this index.
   *
   * @var \Drupal\search_api\Processor\ProcessorInterface[]|null
   *
   * @see loadProcessors()
   */
  protected $processors;

  /**
   * List of types that failed to map to a Search API type.
   *
   * The unknown types are the keys and map to arrays of fields that were
   * ignored because they are of this type.
   *
   * @var string[][]
   */
  protected $unmappedFields = array();

  /**
   * {@inheritdoc}
   */
  public function __construct(array $values, $entity_type) {
    parent::__construct($values, $entity_type);

    // Merge in default options.
    // @todo Use a dedicated method, like defaultConfiguration() for plugins?
    //   And/or, better still, do this in postCreate() (and preSave()?) and not
    //   on every load.
    $this->options += array(
      'cron_limit' => \Drupal::config('search_api.settings')->get('default_cron_limit'),
      'index_directly' => TRUE,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function isReadOnly() {
    return $this->read_only;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheId($type = 'fields') {
    return 'search_api_index:' . $this->id() . ':' . $type;
  }

  /**
   * {@inheritdoc}
   */
  public function getOption($name, $default = NULL) {
    return isset($this->options[$name]) ? $this->options[$name] : $default;
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions() {
    return $this->options;
  }

  /**
   * {@inheritdoc}
   */
  public function setOption($name, $option) {
    $this->options[$name] = $option;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOptions(array $options) {
    $this->options = $options;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDatasourceIds() {
    return $this->datasources;
  }

  /**
   * {@inheritdoc}
   */
  public function isValidDatasource($datasource_id) {
    $datasources = $this->getDatasources();
    return !empty($datasources[$datasource_id]);
  }

  /**
   * {@inheritdoc}
   */
  public function getDatasource($datasource_id) {
    $datasources = $this->getDatasources();
    if (empty($datasources[$datasource_id])) {
      $args['@datasource'] = $datasource_id;
      $args['%index'] = $this->label();
      throw new SearchApiException(new FormattableMarkup('The datasource with ID "@datasource" could not be retrieved for index %index.', $args));
    }
    return $datasources[$datasource_id];
  }

  /**
   * {@inheritdoc}
   */
  public function getDatasources($only_enabled = TRUE) {
    if (!isset($this->datasourcePlugins)) {
      $this->datasourcePlugins = array();
      /** @var $datasource_plugin_manager \Drupal\search_api\Datasource\DatasourcePluginManager */
      $datasource_plugin_manager = \Drupal::service('plugin.manager.search_api.datasource');

      foreach ($datasource_plugin_manager->getDefinitions() as $name => $datasource_definition) {
        if (class_exists($datasource_definition['class']) && empty($this->datasourcePlugins[$name])) {
          // Create our settings for this datasource.
          $config = isset($this->datasource_configs[$name]) ? $this->datasource_configs[$name] : array();
          $config += array('index' => $this);

          /** @var $datasource \Drupal\search_api\Datasource\DatasourceInterface */
          $datasource = $datasource_plugin_manager->createInstance($name, $config);
          $this->datasourcePlugins[$name] = $datasource;
        }
        elseif (!class_exists($datasource_definition['class'])) {
          \Drupal::logger('search_api')->warning('Datasource @id specifies a non-existing @class.', array('@id' => $name, '@class' => $datasource_definition['class']));
        }
      }
    }

    // Filter datasources by status if required.
    if (!$only_enabled) {
      return $this->datasourcePlugins;
    }
    return array_intersect_key($this->datasourcePlugins, array_flip($this->datasources));
  }

  /**
   * {@inheritdoc}
   */
  public function hasValidTracker() {
    return (bool) \Drupal::service('plugin.manager.search_api.tracker')->getDefinition($this->getTrackerId(), FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function getTrackerId() {
    return $this->tracker;
  }

  /**
   * {@inheritdoc}
   */
  public function getTracker() {
    if (!$this->trackerPlugin) {
      $tracker_plugin_configuration = array('index' => $this) + $this->tracker_config;
      if (!($this->trackerPlugin = \Drupal::service('plugin.manager.search_api.tracker')->createInstance($this->getTrackerId(), $tracker_plugin_configuration))) {
        $args['@tracker'] = $this->tracker;
        $args['%index'] = $this->label();
        throw new SearchApiException(new FormattableMarkup('The tracker with ID "@tracker" could not be retrieved for index %index.', $args));
      }
    }

    return $this->trackerPlugin;
  }

  /**
   * {@inheritdoc}
   */
  public function hasValidServer() {
    return $this->server !== NULL && Server::load($this->server) !== NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function isServerEnabled() {
    return $this->hasValidServer() && $this->getServer()->status();
  }

  /**
   * {@inheritdoc}
   */
  public function getServerId() {
    return $this->server;
  }

  /**
   * {@inheritdoc}
   */
  public function getServer() {
    if (!$this->serverInstance && $this->server) {
      $this->serverInstance = Server::load($this->server);
      if (!$this->serverInstance) {
        $args['@server'] = $this->server;
        $args['%index'] = $this->label();
        throw new SearchApiException(new FormattableMarkup('The server with ID "@server" could not be retrieved for index %index.', $args));
      }
    }

    return $this->serverInstance;
  }

  /**
   * {@inheritdoc}
   */
  public function setServer(ServerInterface $server = NULL) {
    $this->serverInstance = $server;
    $this->server = $server ? $server->id() : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getProcessors($only_enabled = TRUE) {
    $processors = $this->loadProcessors();

    // Filter processors by status if required. Enabled processors are those
    // which have settings in the "processors" option.
    if ($only_enabled) {
      $processors_settings = $this->getOption('processors', array());
      $processors = array_intersect_key($processors, $processors_settings);
    }

    return $processors;
  }

  /**
   * {@inheritdoc}
   */
  public function getProcessorsByStage($stage, $only_enabled = TRUE) {
    $processors = $this->loadProcessors();
    $processor_settings = $this->getOption('processors', array());
    $processor_weights = array();

    // Get a list of all processors meeting the criteria (stage and, optionally,
    // enabled) along with their effective weights (user-set or default).
    foreach ($processors as $name => $processor) {
      if ($processor->supportsStage($stage) && !($only_enabled && empty($processor_settings[$name]))) {
        if (!empty($processor_settings[$name]['weights'][$stage])) {
          $processor_weights[$name] = $processor_settings[$name]['weights'][$stage];
        }
        else {
          $processor_weights[$name] = $processor->getDefaultWeight($stage);
        }
      }
    }

    // Sort requested processors by weight.
    asort($processor_weights);

    $return_processors = array();
    foreach ($processor_weights as $name => $weight) {
      $return_processors[$name] = $processors[$name];
    }
    return $return_processors;
  }

  /**
   * Retrieves all processors supported by this index.
   *
   * @return \Drupal\search_api\Processor\ProcessorInterface[]
   *   The loaded processors, keyed by processor ID.
   */
  protected function loadProcessors() {
    if (!isset($this->processors)) {
      /** @var $processor_plugin_manager \Drupal\search_api\Processor\ProcessorPluginManager */
      $processor_plugin_manager = \Drupal::service('plugin.manager.search_api.processor');
      $processor_settings = $this->getOption('processors', array());

      foreach ($processor_plugin_manager->getDefinitions() as $name => $processor_definition) {
        if (class_exists($processor_definition['class']) && empty($this->processors[$name])) {
          // Create our settings for this processor.
          $settings = empty($processor_settings[$name]['settings']) ? array() : $processor_settings[$name]['settings'];
          $settings['index'] = $this;

          /** @var $processor \Drupal\search_api\Processor\ProcessorInterface */
          $processor = $processor_plugin_manager->createInstance($name, $settings);
          if ($processor->supportsIndex($this)) {
            $this->processors[$name] = $processor;
          }
        }
        elseif (!class_exists($processor_definition['class'])) {
          \Drupal::logger('search_api')->warning('Processor @id specifies a non-existing @class.', array('@id' => $name, '@class' => $processor_definition['class']));
        }
      }
    }

    return $this->processors;
  }

  /**
   * {@inheritdoc}
   */
  public function preprocessIndexItems(array &$items) {
    foreach ($this->getProcessorsByStage(ProcessorInterface::STAGE_PREPROCESS_INDEX) as $processor) {
      $processor->preprocessIndexItems($items);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preprocessSearchQuery(QueryInterface $query) {
    foreach ($this->getProcessorsByStage(ProcessorInterface::STAGE_PREPROCESS_QUERY) as $processor) {
      $processor->preprocessSearchQuery($query);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function postprocessSearchResults(ResultSetInterface $results) {
    /** @var $processor \Drupal\search_api\Processor\ProcessorInterface */
    foreach (array_reverse($this->getProcessorsByStage(ProcessorInterface::STAGE_POSTPROCESS_QUERY)) as $processor) {
      $processor->postprocessSearchResults($results);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFields($only_indexed = TRUE) {
    $this->computeFields();
    $only_indexed = $only_indexed ? 1 : 0;
    return $this->fields[$only_indexed]['fields'];
  }

  /**
   * Populates the $fields property with information about the index's fields.
   *
   * Used by getFields(), getFieldsByDatasource(), getAdditionalFields() and
   * getAdditionalFieldsByDatasource().
   */
  protected function computeFields() {
    // First, try the static cache and the persistent cache bin.
    // @todo Since labels and descriptions are translated, we probably need to
    //   cache per language?
    $cid = $this->getCacheId();
    if (empty($this->fields)) {
      if ($cached = \Drupal::cache()->get($cid)) {
        $this->fields = $cached->data;
        if ($this->fields) {
          $this->updateFieldsIndex($this->fields);
        }
      }
    }

    // If not cached, fetch the list of fields and their properties.
    if (empty($this->fields)) {
      $this->fields = array(
        0 => array(
          'fields' => array(),
          'additional fields' => array(),
        ),
        1 => array(
          'fields' => array(),
        ),
      );
      // Remember the fields for which we couldn't find a mapping.
      $this->unmappedFields = array();
      foreach (array_merge(array(NULL), $this->datasources) as $datasource_id) {
        try {
          $this->convertPropertyDefinitionsToFields($this->getPropertyDefinitions($datasource_id), $datasource_id);
        }
        catch (SearchApiException $e) {
          $variables['%index'] = $this->label();
          watchdog_exception('search_api', $e, '%type while retrieving fields for index %index: @message in %function (line %line of %file).', $variables);
        }
      }
      if ($this->unmappedFields) {
        $vars['@fields'] = array();
        foreach ($this->unmappedFields as $type => $fields) {
          $vars['@fields'][] = implode(', ', $fields) . ' (' . new FormattableMarkup('type @type', array('@type' => $type)) . ')';
        }
        $vars['@fields'] = implode('; ', $vars['@fields']);
        $vars['%index'] = $this->label();
        \Drupal::logger('search_api')->warning('Warning while retrieving available fields for index %index: could not find a type mapping for the following fields: @fields.', $vars);
      }
      \Drupal::cache()->set($cid, $this->fields, Cache::PERMANENT, $this->getCacheTags());
    }
  }

  /**
   * Sets this object as the index for all fields contained in the given array.
   *
   * This is important when loading fields from the cache, because their index
   * objects might then point to another instance of this index.
   *
   * @param array $fields
   *   An array containing various values, some of which might be
   *   \Drupal\search_api\Item\GenericFieldInterface objects and some of which
   *   might be nested arrays containing such objects.
   */
  protected function updateFieldsIndex(array $fields) {
    foreach ($fields as $value) {
      if (is_array($value)) {
        $this->updateFieldsIndex($value);
      }
      elseif ($value instanceof GenericFieldInterface) {
        $value->setIndex($this);
      }
    }
  }

  /**
   * Converts an array of property definitions into Search API field objects.
   *
   * Stores the resulting values in $this->fields, to be returned by subsequent
   * getFields() calls.
   *
   * @param \Drupal\Core\TypedData\DataDefinitionInterface[] $properties
   *   An array of properties on some complex data object.
   * @param string|null $datasource_id
   *   (optional) The ID of the datasource to which these properties belong.
   * @param string $prefix
   *   Internal use only. A prefix to use for the generated field names in this
   *   method.
   * @param string $label_prefix
   *   Internal use only. A prefix to use for the generated field labels in this
   *   method.
   *
   * @throws \Drupal\search_api\SearchApiException
   *   Thrown if $datasource_id is not valid datasource for this index.
   */
  protected function convertPropertyDefinitionsToFields(array $properties, $datasource_id = NULL, $prefix = '', $label_prefix = '') {
    $type_mapping = Utility::getFieldTypeMapping();
    $field_options = isset($this->options['fields']) ? $this->options['fields'] : array();
    $enabled_additional_fields = isset($this->options['additional fields']) ? $this->options['additional fields'] : array();

    // All field identifiers should start with the datasource ID.
    if (!$prefix && $datasource_id) {
      $prefix = $datasource_id . self::DATASOURCE_ID_SEPARATOR;
    }
    $datasource_label = $datasource_id ? $this->getDatasource($datasource_id)->label() . ' » ' : '';

    // Loop over all properties and handle them accordingly.
    $recurse = array();
    foreach ($properties as $property_path => $property) {
      $original_property = $property;
      if ($property instanceof PropertyInterface) {
        $property = $property->getWrappedProperty();
      }
      $key = "$prefix$property_path";
      $label = $label_prefix . $property->getLabel();
      $description = $property->getDescription();
      while ($property instanceof ListDataDefinitionInterface) {
        $property = $property->getItemDefinition();
      }
      while ($property instanceof DataReferenceDefinitionInterface) {
        $property = $property->getTargetDefinition();
      }
      if ($property instanceof ComplexDataDefinitionInterface) {
        $main_property = $property->getMainPropertyName();
        $nested_properties = $property->getPropertyDefinitions();

        // Don't add the additional 'entity' property for entity reference
        // fields which don't target a content entity type.
        $referenced_entity_type_label = NULL;
        if ($property instanceof FieldItemDataDefinition && in_array($property->getDataType(), array('field_item:entity_reference', 'field_item:image', 'field_item:file'))) {
          $entity_type = $this->entityManager()->getDefinition($property->getSetting('target_type'));
          if (!$entity_type->isSubclassOf('Drupal\Core\Entity\ContentEntityInterface')) {
            unset($nested_properties['entity']);
          }
          else {
            $referenced_entity_type_label = $entity_type->getLabel();
          }
        }

        $additional = count($nested_properties) > 1;
        if (!empty($enabled_additional_fields[$key]) && $nested_properties) {
          // We allow the main property to be indexed directly, so we don't
          // have to add it again for the nested fields.
          if ($main_property) {
            unset($nested_properties[$main_property]);
          }
          if ($nested_properties) {
            $additional = TRUE;
            $recurse[] = array($nested_properties, $datasource_id, "$key:", "$label » ");
          }
        }

        if ($additional) {
          $additional_field = Utility::createAdditionalField($this, $key);
          $additional_field->setLabel("$label [$key]");
          $additional_field->setDescription($description);
          $additional_field->setEnabled(!empty($enabled_additional_fields[$key]));
          $additional_field->setLocked(FALSE);
          if ($original_property instanceof PropertyInterface) {
            $additional_field->setHidden($original_property->isHidden());
          }
          $this->fields[0]['additional fields'][$key] = $additional_field;
          if ($additional_field->isEnabled()) {
            while ($pos = strrpos($property_path, ':')) {
              $property_path = substr($property_path, 0, $pos);
              /** @var \Drupal\search_api\Item\AdditionalFieldInterface $additional_field */
              $additional_field = $this->fields[0]['additional fields'][$property_path];
              $additional_field->setEnabled(TRUE);
              $additional_field->setLocked();
            }
          }
        }
        // If the complex data type has a main property, we can index that
        // directly here. Otherwise, we don't add it and continue with the next
        // property.
        if (!$main_property) {
          continue;
        }
        $parent_type = $property->getDataType();
        $property = $property->getPropertyDefinition($main_property);
        if (!$property) {
          continue;
        }

        // If there are additional properties, add the label for the main
        // property to make it clear what it refers to.
        if ($additional) {
          $nested_label = $property->getLabel();
          if ($referenced_entity_type_label) {
            $nested_label = str_replace('@label', $referenced_entity_type_label, $nested_label);
          }

          $label .= ' » ' . $nested_label;
        }
      }

      $type = $property->getDataType();
      // Try to see if there's a mapping for a parent.child data type.
      if (isset($parent_type) && isset($type_mapping[$parent_type . '.' . $type])) {
        $field_type = $type_mapping[$parent_type . '.' . $type];
      }
      elseif (!empty($type_mapping[$type])) {
        $field_type = $type_mapping[$type];
      }
      else {
        // Failed to map this type, skip.
        if (!isset($type_mapping[$type])) {
          $this->unmappedFields[$type][$key] = $key;
        }
        continue;
      }

      $field = Utility::createField($this, $key);
      $field->setType($field_type);
      $field->setLabel($label);
      $field->setLabelPrefix($datasource_label);
      $field->setDescription($description);
      $field->setIndexed(FALSE);
      // To make it possible to lock fields that are, technically, nested, use
      // the original $property for this check.
      if ($original_property instanceof PropertyInterface) {
        $field->setIndexedLocked($original_property->isIndexedLocked());
        $field->setTypeLocked($original_property->isTypeLocked());
        $field->setHidden($original_property->isHidden());
      }
      $this->fields[0]['fields'][$key] = $field;
      if (isset($field_options[$key]) || $field->isIndexedLocked()) {
        $field->setIndexed(TRUE);
        if (isset($field_options[$key])) {
          $field->setType($field_options[$key]['type']);
          if (isset($field_options[$key]['boost'])) {
            $field->setBoost($field_options[$key]['boost']);
          }
        }
        $this->fields[1]['fields'][$key] = $field;
      }
    }
    foreach ($recurse as $arguments) {
      call_user_func_array(array($this, 'convertPropertyDefinitionsToFields'), $arguments);
    }

    // Order unindexed fields alphabetically.
    $sort_by_label = function(GenericFieldInterface $field1, GenericFieldInterface $field2) {
      return strnatcasecmp($field1->getLabel(), $field2->getLabel());
    };
    uasort($this->fields[0]['fields'], $sort_by_label);
    uasort($this->fields[0]['additional fields'], $sort_by_label);
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldsByDatasource($datasource_id, $only_indexed = TRUE) {
    $only_indexed = $only_indexed ? 1 : 0;
    if (!isset($this->datasourceFields)) {
      $this->computeFields();
      $this->datasourceFields = array_fill_keys($this->datasources, array(array(), array()));
      $this->datasourceFields[NULL] = array(array(), array());
      /** @var \Drupal\search_api\Item\FieldInterface $field */
      foreach ($this->fields[0]['fields'] as $field_id => $field) {
        $this->datasourceFields[$field->getDatasourceId()][0][$field_id] = $field;
        if ($field->isIndexed()) {
          $this->datasourceFields[$field->getDatasourceId()][1][$field_id] = $field;
        }
      }
    }
    return $this->datasourceFields[$datasource_id][$only_indexed];
  }

  /**
   * {@inheritdoc}
   */
  public function getAdditionalFields() {
    $this->computeFields();
    return $this->fields[0]['additional fields'];
  }

  /**
   * {@inheritdoc}
   */
  public function getAdditionalFieldsByDatasource($datasource_id) {
    if (!isset($this->datasourceAdditionalFields)) {
      $this->computeFields();
      $this->datasourceAdditionalFields = array_fill_keys($this->datasources, array());
      $this->datasourceAdditionalFields[NULL] = array();
      /** @var \Drupal\search_api\Item\FieldInterface $field */
      foreach ($this->fields[0]['additional fields'] as $field_id => $field) {
        $this->datasourceAdditionalFields[$field->getDatasourceId()][$field_id] = $field;
      }
    }
    return $this->datasourceAdditionalFields[$datasource_id];
  }

  /**
   * {@inheritdoc}
   */
  public function getFulltextFields($only_indexed = TRUE) {
    $i = $only_indexed ? 1 : 0;
    if (!isset($this->fulltextFields[$i])) {
      $this->fulltextFields[$i] = array();
      if ($only_indexed) {
        if (isset($this->options['fields'])) {
          foreach ($this->options['fields'] as $key => $field) {
            if (Utility::isTextType($field['type'])) {
              $this->fulltextFields[$i][] = $key;
            }
          }
        }
      }
      else {
        foreach ($this->getFields(FALSE) as $key => $field) {
          if (Utility::isTextType($field->getType())) {
            $this->fulltextFields[$i][] = $key;
          }
        }
      }
    }
    return $this->fulltextFields[$i];
  }

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions($datasource_id, $alter = TRUE) {
    $alter = $alter ? 1 : 0;
    if (!isset($this->properties[$datasource_id][$alter])) {
      if (isset($datasource_id)) {
        $datasource = $this->getDatasource($datasource_id);
        $this->properties[$datasource_id][$alter] = $datasource->getPropertyDefinitions();
      }
      else {
        $datasource = NULL;
        $this->properties[$datasource_id][$alter] = array();
      }
      if ($alter) {
        foreach ($this->getProcessors() as $processor) {
          $processor->alterPropertyDefinitions($this->properties[$datasource_id][$alter], $datasource);
        }
      }
    }
    return $this->properties[$datasource_id][$alter];
  }

  /**
   * {@inheritdoc}
   */
  public function loadItem($item_id) {
    $items = $this->loadItemsMultiple(array($item_id));
    return $items ? reset($items) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function loadItemsMultiple(array $item_ids, $group_by_datasource = FALSE) {
    $items_by_datasource = array();
    foreach ($item_ids as $item_id) {
      list($datasource_id, $raw_id) = Utility::splitCombinedId($item_id);
      $items_by_datasource[$datasource_id][$item_id] = $raw_id;
    }
    $items = array();
    foreach ($items_by_datasource as $datasource_id => $raw_ids) {
      try {
        foreach ($this->getDatasource($datasource_id)->loadMultiple($raw_ids) as $raw_id => $item) {
          $id = Utility::createCombinedId($datasource_id, $raw_id);
          if ($group_by_datasource) {
            $items[$datasource_id][$id] = $item;
          }
          else {
            $items[$id] = $item;
          }
        }
      }
      catch (SearchApiException $e) {
        watchdog_exception('search_api', $e);
      }
    }
    return $items;
  }

  /**
   * {@inheritdoc}
   */
  public function index($limit = '-1', $datasource_id = NULL) {
    if ($this->hasValidTracker() && !$this->isReadOnly()) {
      $tracker = $this->getTracker();
      $next_set = $tracker->getRemainingItems($limit, $datasource_id);
      $items = $this->loadItemsMultiple($next_set);
      if ($items) {
        try {
          return count($this->indexItems($items));
        }
        catch (SearchApiException $e) {
          $variables['%index'] = $this->label();
          watchdog_exception('search_api', $e, '%type while trying to index items on index %index: @message in %function (line %line of %file)', $variables);
        }
      }
    }
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function indexItems(array $search_objects) {
    if (!$search_objects || $this->read_only) {
      return array();
    }
    if (!$this->status) {
      throw new SearchApiException(new FormattableMarkup("Couldn't index values on index %index (index is disabled)", array('%index' => $this->label())));
    }
    if (empty($this->options['fields'])) {
      throw new SearchApiException(new FormattableMarkup("Couldn't index values on index %index (no fields selected)", array('%index' => $this->label())));
    }

    /** @var \Drupal\search_api\Item\ItemInterface[] $items */
    $items = array();
    foreach ($search_objects as $item_id => $object) {
      $items[$item_id] = Utility::createItemFromObject($this, $object, $item_id);
      // This will cache the extracted fields so processors, etc., can retrieve
      // them directly.
      $items[$item_id]->getFields();
    }

    // Remember the items that were initially passed, to be able to determine
    // the items rejected by alter hooks and processors afterwards.
    $rejected_ids = array_keys($items);
    $rejected_ids = array_combine($rejected_ids, $rejected_ids);

    // Preprocess the indexed items.
    \Drupal::moduleHandler()->alter('search_api_index_items', $this, $items);
    $this->preprocessIndexItems($items);

    // Remove all items still in $items from $rejected_ids. Thus, only the
    // rejected items' IDs are still contained in $ret, to later be returned
    // along with the successfully indexed ones.
    foreach ($items as $item_id => $item) {
      unset($rejected_ids[$item_id]);
    }

    // Items that are rejected should also be deleted from the server.
    if ($rejected_ids) {
      $this->getServer()->deleteItems($this, $rejected_ids);
    }

    $indexed_ids = array();
    if ($items) {
      $indexed_ids = $this->getServer()->indexItems($this, $items);
    }
    // Return the IDs of all items that were either successfully indexed or
    // rejected before being handed to the server.
    $processed_ids = array_merge(array_values($rejected_ids), array_values($indexed_ids));
    if ($this->hasValidTracker()) {
      $this->getTracker()->trackItemsIndexed($processed_ids);
    }
    \Drupal::moduleHandler()->invokeAll('search_api_items_indexed', array($this, $processed_ids));
    return $processed_ids;
  }

  /**
   * {@inheritdoc}
   */
  public function trackItemsInserted($datasource_id, array $ids) {
    $this->trackItemsInsertedOrUpdated($datasource_id, $ids, __FUNCTION__);
  }

  /**
   * {@inheritdoc}
   */
  public function trackItemsUpdated($datasource_id, array $ids) {
    $this->trackItemsInsertedOrUpdated($datasource_id, $ids, __FUNCTION__);
  }

  /**
   * Tracks insertion or updating of items.
   *
   * Used as a helper method in trackItemsInserted() and trackItemsUpdated() to
   * avoid code duplication.
   *
   * @param string $datasource_id
   *   The ID of the datasource to which the items belong.
   * @param array $ids
   *   An array of datasource-specific item IDs.
   * @param string $tracker_method
   *   The method to call on the tracker. Must be either "trackItemsInserted" or
   *   "trackItemsUpdated".
   */
  protected function trackItemsInsertedOrUpdated($datasource_id, array $ids, $tracker_method) {
    if ($this->hasValidTracker() && $this->status() && Utility::getIndexTaskManager()->isTrackingComplete($this)) {
      $item_ids = array();
      foreach ($ids as $id) {
        $item_ids[] = Utility::createCombinedId($datasource_id, $id);
      }
      $this->getTracker()->$tracker_method($item_ids);
      if (!$this->isReadOnly() && $this->getOption('index_directly')) {
        try {
          $items = $this->loadItemsMultiple($item_ids);
          if ($items) {
            $this->indexItems($items);
          }
        }
        catch (SearchApiException $e) {
          watchdog_exception('search_api', $e);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function trackItemsDeleted($datasource_id, array $ids) {
    if ($this->hasValidTracker() && $this->status()) {
      $item_ids = array();
      foreach ($ids as $id) {
        $item_ids[] = Utility::createCombinedId($datasource_id, $id);
      }
      $this->getTracker()->trackItemsDeleted($item_ids);
      if (!$this->isReadOnly() && $this->isServerEnabled()) {
        $this->getServer()->deleteItems($this, $item_ids);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function reindex() {
    if ($this->status()) {
      $this->getTracker()->trackAllItemsUpdated();
      \Drupal::moduleHandler()->invokeAll('search_api_index_reindex', array($this, FALSE));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function clear() {
    if ($this->status()) {
      $this->getTracker()->trackAllItemsUpdated();
      if (!$this->isReadOnly()) {
        $this->getServer()->deleteAllIndexItems($this);
      }
      \Drupal::moduleHandler()->invokeAll('search_api_index_reindex', array($this, !$this->isReadOnly()));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function resetCaches($include_stored = TRUE) {
    $this->datasourcePlugins = NULL;
    $this->trackerPlugin = NULL;
    $this->serverInstance = NULL;
    $this->fields = NULL;
    $this->datasourceFields = NULL;
    $this->fulltextFields = NULL;
    $this->processors = NULL;
    $this->properties = NULL;
    $this->datasourceAdditionalFields = NULL;
    if ($include_stored) {
      Cache::invalidateTags($this->getCacheTags());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function query(array $options = array()) {
    if (!$this->status()) {
      throw new SearchApiException('Cannot search on a disabled index.');
    }
    return Utility::createQuery($this, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    // Stop enabling of indexes when the server is disabled.
    if ($this->status() && !$this->isServerEnabled()) {
      $this->disable();
    }

    $this->applyLockedConfiguration();
  }

  /**
   * Makes sure that locked processors and fields are actually enabled/indexed.
   *
   * @return bool
   *   TRUE if any changes were made to the index as a result of this operation;
   *   FALSE otherwise.
   */
  public function applyLockedConfiguration() {
    $change = FALSE;
    // Obviously, we should first check for locked processors, because they
    // might add new locked properties.
    foreach ($this->getProcessors(FALSE) as $processor_id => $processor) {
      if ($processor->isLocked() && !isset($this->options['processors'][$processor_id])) {
        $this->options['processors'][$processor_id] = array(
          'processor_id' => $processor_id,
          'weights' => array(),
          'settings' => array(),
        );
        $change = TRUE;
      }
    }
    if ($change) {
      $this->resetCaches(FALSE);
    }
    $datasource_ids = array_merge(array(NULL), $this->getDatasourceIds());
    foreach ($datasource_ids as $datasource_id) {
      foreach ($this->getPropertyDefinitions($datasource_id) as $key => $property) {
        if ($property instanceof PropertyInterface && $property->isIndexedLocked()) {
          $settings = $property->getFieldSettings();
          if (empty($settings['type'])) {
            $mapping = Utility::getFieldTypeMapping();
            $type = $property->getDataType();
            $settings['type'] = !empty($mapping[$type]) ? $mapping[$type] : 'string';
          }
          $this->options['fields'][$key] = $settings;
          $change = TRUE;
        }
      }
    }
    return $change;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);
    $this->resetCaches();

    try {
      // Fake an original for inserts to make code cleaner.
      $original = $update ? $this->original : static::create(array('status' => FALSE));

      if ($this->status() && $original->status()) {
        // React on possible changes that would require re-indexing, etc.
        $this->reactToServerSwitch($original);
        $this->reactToDatasourceSwitch($original);
        $this->reactToTrackerSwitch($original);
      }
      elseif (!$this->status() && $original->status()) {
        if ($this->hasValidTracker()) {
          Utility::getIndexTaskManager()->stopTracking($this);
        }
        if ($original->isServerEnabled()) {
          $original->getServer()->removeIndex($this);
        }
      }
      elseif ($this->status() && !$original->status()) {
        $this->getServer()->addIndex($this);
        if ($this->hasValidTracker()) {
          Utility::getIndexTaskManager()->startTracking($this);
        }
      }

      $index_task_manager = Utility::getIndexTaskManager();
      if (!$index_task_manager->isTrackingComplete($this)) {
        // Give tests and site admins the possibility to disable the use of a
        // batch for tracking items. Also, do not use a batch if running in the
        // CLI.
        $use_batch = \Drupal::state()->get('search_api_use_tracking_batch', TRUE);
        if (!$use_batch || php_sapi_name() == 'cli') {
          $index_task_manager->addItemsAll($this);
        }
        else {
          $index_task_manager->addItemsBatch($this);
        }
      }

      if (\Drupal::moduleHandler()->moduleExists('views')) {
        Views::viewsData()->clear();
        // Remove this line when https://www.drupal.org/node/2370365 gets fixed.
        Cache::invalidateTags(array('extension:views'));
        \Drupal::cache('discovery')->delete('views:wizard');
      }

      $this->resetCaches();
    }
    catch (SearchApiException $e) {
      watchdog_exception('search_api', $e);
    }
  }

  /**
   * Checks whether the index switched server and reacts accordingly.
   *
   * Used as a helper method in postSave(). Should only be called when the index
   * was enabled before the change and remained so.
   *
   * @param \Drupal\search_api\IndexInterface $original
   *   The previous version of the index.
   */
  protected function reactToServerSwitch(IndexInterface $original) {
    if ($this->getServerId() != $original->getServerId()) {
      if ($original->isServerEnabled()) {
        $original->getServer()->removeIndex($this);
      }
      if ($this->isServerEnabled()) {
        $this->getServer()->addIndex($this);
      }
      // When the server changes we also need to trigger a reindex.
      $this->reindex();
    }
    elseif ($this->isServerEnabled()) {
      // Tell the server the index configuration got updated
      $this->getServer()->updateIndex($this);
    }
  }

  /**
   * Checks whether the index's datasources changed and reacts accordingly.
   *
   * Used as a helper method in postSave(). Should only be called when the index
   * was enabled before the change and remained so.
   *
   * @param \Drupal\search_api\IndexInterface $original
   *   The previous version of the index.
   */
  protected function reactToDatasourceSwitch(IndexInterface $original) {
    $new_datasource_ids = $this->getDatasourceIds();
    $original_datasource_ids = $original->getDatasourceIds();
    if ($new_datasource_ids != $original_datasource_ids) {
      $added = array_diff($new_datasource_ids, $original_datasource_ids);
      $removed = array_diff($original_datasource_ids, $new_datasource_ids);
      $index_task_manager = Utility::getIndexTaskManager();
      $index_task_manager->stopTracking($this, $removed);
      $index_task_manager->startTracking($this, $added);
    }
  }


  /**
   * Checks whether the index switched tracker plugin and reacts accordingly.
   *
   * Used as a helper method in postSave(). Should only be called when the index
   * was enabled before the change and remained so.
   *
   * @param \Drupal\search_api\IndexInterface $original
   *   The previous version of the index.
   */
  protected function reactToTrackerSwitch(IndexInterface $original) {
    if ($this->tracker != $original->getTrackerId()) {
      $index_task_manager = Utility::getIndexTaskManager();
      if ($original->hasValidTracker()) {
        $index_task_manager->stopTracking($this);
      }
      if ($this->hasValidTracker()) {
        $index_task_manager->startTracking($this);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageInterface $storage, array $entities) {
    parent::preDelete($storage, $entities);
    /** @var \Drupal\search_api\IndexInterface[] $entities */
    foreach ($entities as $index) {
      if ($index->hasValidTracker()) {
        $index->getTracker()->trackAllItemsDeleted();
      }
      if ($index->hasValidServer()) {
        $index->getServer()->removeIndex($index);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);

    if (\Drupal::moduleHandler()->moduleExists('views')) {
      Views::viewsData()->clear();
      // Remove this line when https://www.drupal.org/node/2370365 gets fixed.
      Cache::invalidateTags(array('extension:views'));
      \Drupal::cache('discovery')->delete('views:wizard');
    }
  }

  // @todo Override static load() etc. methods? Measure performance difference.

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();

    // Add a dependency on the server, if there is one set.
    if ($this->hasValidServer()) {
      $this->addDependency('config', $this->getServer()->getConfigDependencyName());
    }
    // Add dependencies for all of the index's plugins.
    if ($this->hasValidTracker()) {
      $this->calculatePluginDependencies($this->getTracker());
    }
    foreach ($this->getProcessors() as $processor) {
      $this->calculatePluginDependencies($processor);
    }
    foreach ($this->getDatasources() as $datasource) {
      $this->calculatePluginDependencies($datasource);
    }

    return $this->dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function onDependencyRemoval(array $dependencies) {
    $changed = parent::onDependencyRemoval($dependencies);

    // @todo Also react sensibly when removing the dependency of a plugin or an
    //   indexed field. See #2574633 and #2541206.
    foreach ($dependencies['config'] as $entity) {
      if ($entity instanceof EntityInterface && $entity->getEntityTypeId() == 'search_api_server') {
        // Remove this index from the deleted server (thus disabling it).
        $this->setServer(NULL);
        $this->setStatus(FALSE);
        $changed = TRUE;
      }
    }

    return $changed;
  }

  /**
   * Implements the magic __clone() method.
   *
   * Prevents the cached plugins and fields from being cloned, too (since they
   * would then point to the wrong index object).
   */
  public function __clone() {
    $this->resetCaches(FALSE);
  }

  /**
   * Implements the magic __sleep() method.
   *
   * Prevents the cached plugins and fields from being serialized.
   */
  public function __sleep() {
    $properties = get_object_vars($this);
    unset($properties['datasourcePlugins']);
    unset($properties['trackerPlugin']);
    unset($properties['serverInstance']);
    unset($properties['fields']);
    unset($properties['datasourceFields']);
    unset($properties['fulltextFields']);
    unset($properties['processors']);
    unset($properties['properties']);
    unset($properties['datasourceAdditionalFields']);
    return array_keys($properties);
  }

}
