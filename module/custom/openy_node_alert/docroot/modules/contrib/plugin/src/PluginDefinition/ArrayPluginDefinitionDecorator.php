<?php

namespace Drupal\plugin\PluginDefinition;

use Drupal\Component\Plugin\Context\ContextDefinitionInterface;
use Drupal\Component\Utility\NestedArray;

/**
 * Provides a plugin definition based on an array.
 *
 * @ingroup Plugin
 */
class ArrayPluginDefinitionDecorator implements ArrayPluginDefinitionInterface, PluginContextDefinitionInterface, PluginDeriverDefinitionInterface, PluginLabelDefinitionInterface, PluginDescriptionDefinitionInterface, PluginCategoryDefinitionInterface, PluginConfigDependenciesDefinitionInterface, PluginDefinitionDecoratorInterface, PluginHierarchyDefinitionInterface, PluginOperationsProviderDefinitionInterface {

  use MergeablePluginDefinitionTrait;

  /**
   * The array definition.
   *
   * @var mixed[]
   */
  protected $arrayDefinition = [];

  /**
   * Constructs a new instance.
   *
   * @param array $array_definition
   *   The array definition.
   */
  public function __construct(array $array_definition = []) {
    if (isset($array_definition['class'])) {
      PluginDefinitionValidator::validateClass($array_definition['class']);
    }
    if (isset($array_definition['deriver'])) {
      PluginDefinitionValidator::validateDeriverClass($array_definition['deriver']);
    }
    if (isset($array_definition['context'])) {
      PluginDefinitionValidator::validateContextDefinitions($array_definition['context']);
    }
    $this->arrayDefinition = $array_definition;
  }

  /**
   * {@inheritdoc}
   */
  public static function createFromDecoratedDefinition($decorated_plugin_definition) {
    if (!is_array($decorated_plugin_definition)) {
      $type = is_object($decorated_plugin_definition) ? get_class($decorated_plugin_definition) : gettype($decorated_plugin_definition);
      throw new \InvalidArgumentException(sprintf('The decorated plugin definition must be an array, but %s given.', $type));
    }

    return new static($decorated_plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function getArrayDefinition() {
    return $this->arrayDefinition;
  }

  /**
   * {@inheritdoc}
   */
  public function mergeDefaultArrayDefinition(array $other_definition) {
    $this->arrayDefinition = NestedArray::mergeDeepArray([$other_definition, $this->arrayDefinition], TRUE);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function mergeOverrideArrayDefinition(array $other_definition) {
    $this->arrayDefinition = NestedArray::mergeDeepArray([$this->arrayDefinition, $other_definition], TRUE);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setId($id) {
    $this->arrayDefinition['id'] = $id;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function id() {
    return isset($this->arrayDefinition['id']) ? $this->arrayDefinition['id'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return $this->id();
  }

  /**
   * {@inheritdoc}
   */
  public function setClass($class) {
    PluginDefinitionValidator::validateClass($class);

    $this->arrayDefinition['class'] = $class;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getClass() {
    return isset($this->arrayDefinition['class']) ? $this->arrayDefinition['class'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setLabel($label) {
    $this->arrayDefinition['label'] = $label;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return isset($this->arrayDefinition['label']) ? $this->arrayDefinition['label'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription($description) {
    $this->arrayDefinition['description'] = $description;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return isset($this->arrayDefinition['description']) ? $this->arrayDefinition['description'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setDeriverClass($class) {
    PluginDefinitionValidator::validateDeriverClass($class);

    $this->arrayDefinition['deriver'] = $class;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDeriverClass() {
    return isset($this->arrayDefinition['deriver']) ? $this->arrayDefinition['deriver'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setProvider($provider) {
    $this->arrayDefinition['provider'] = $provider;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getProvider() {
    return isset($this->arrayDefinition['provider']) ? $this->arrayDefinition['provider'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setCategory($category) {
    $this->arrayDefinition['category'] = $category;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCategory() {
    return isset($this->arrayDefinition['category']) ? $this->arrayDefinition['category'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfigDependencies(array $dependencies) {
    $this->arrayDefinition['config_dependencies'] = $dependencies;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigDependencies() {
    return isset($this->arrayDefinition['config_dependencies']) ? $this->arrayDefinition['config_dependencies'] : [];
  }

  /**
   * {@inheritdoc}
   */
  public function setContextDefinitions(array $context_definitions) {
    PluginDefinitionValidator::validateContextDefinitions($context_definitions);

    $this->arrayDefinition['context'] = $context_definitions;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getContextDefinitions() {
    return isset($this->arrayDefinition['context']) ? $this->arrayDefinition['context'] : [];
  }

  /**
   * {@inheritdoc}
   */
  public function setContextDefinition($name, ContextDefinitionInterface $context_definition) {
    $this->arrayDefinition['context'][$name] = $context_definition;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getContextDefinition($name) {
    if (!$this->hasContextDefinition($name)) {
      throw new \InvalidArgumentException(sprintf('Context %s does not exist.', $name));
    }

    return $this->arrayDefinition['context'][$name];
  }

  /**
   * {@inheritdoc}
   */
  public function hasContextDefinition($name) {
    return isset($this->arrayDefinition['context'][$name]);
  }

  /**
   * {@inheritdoc}
   */
  public function doMergeDefaultDefinition(PluginDefinitionInterface $other_definition) {
    /** @var \Drupal\plugin\PluginDefinition\ArrayPluginDefinitionInterface $other_definition */
    $this->mergeDefaultArrayDefinition($other_definition->getArrayDefinition());
  }

  /**
   * {@inheritdoc}
   */
  public function doMergeOverrideDefinition(PluginDefinitionInterface $other_definition) {
    /** @var \Drupal\plugin\PluginDefinition\ArrayPluginDefinitionInterface $other_definition */
    $this->mergeOverrideArrayDefinition($other_definition->getArrayDefinition());
  }

  /**
   * {@inheritdoc}
   */
  protected function isDefinitionCompatible(PluginDefinitionInterface $other_definition) {
    return $other_definition instanceof $this;
  }

  /**
   * {@inheritdoc}
   */
  public function offsetExists($offset) {
    return isset($this->arrayDefinition[$offset]);
  }

  /**
   * {@inheritdoc}
   */
  public function &offsetGet($offset) {
    return $this->arrayDefinition[$offset];
  }

  /**
   * {@inheritdoc}
   */
  public function offsetSet($offset, $value) {
    switch ($offset) {
      case 'class':
        $this->setClass($value);
        break;
      case 'deriver':
        $this->setDeriverClass($value);
        break;
      case 'context':
        $this->setContextDefinitions($value);
        break;
      default:
        $this->arrayDefinition[$offset] = $value;
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function offsetUnset($offset) {
    unset($this->arrayDefinition[$offset]);
  }

  /**
   * {@inheritdoc}
   */
  public function count() {
    return count($this->arrayDefinition);
  }

  /**
   * {@inheritdoc}
   */
  public function getIterator() {
    return new \ArrayIterator($this->arrayDefinition);
  }

  /**
   * {@inheritdoc}
   */
  public function setParentId($id) {
    $this->arrayDefinition['parent_id'] = $id;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getParentId() {
    return isset($this->arrayDefinition['parent_id']) ? $this->arrayDefinition['parent_id'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setOperationsProviderClass($class) {
    $this->arrayDefinition['operations_provider'] = $class;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOperationsProviderClass() {
    return isset($this->arrayDefinition['operations_provider']) ? $this->arrayDefinition['operations_provider'] : NULL;
  }

}
