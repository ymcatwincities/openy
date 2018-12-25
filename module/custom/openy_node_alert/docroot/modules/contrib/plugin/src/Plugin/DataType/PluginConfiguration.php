<?php

namespace Drupal\plugin\Plugin\DataType;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\TypedData;
use Drupal\plugin\Plugin\Field\FieldType\PluginCollectionItemInterface;

/**
 * Provides a plugin configuration data type.
 *
 * @DataType(
 *   id = "plugin_configuration",
 *   label = @Translation("Plugin configuration")
 * )
 */
class PluginConfiguration extends TypedData {

  /**
   * The parent typed data object.
   *
   * @var \Drupal\plugin\Plugin\Field\FieldType\PluginCollectionItemInterface
   */
  protected $parent;

  /**
   * The plugin configuration.
   *
   * @var mixed[]
   */
  protected $value;

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\Core\TypedData\DataDefinitionInterface $definition
   *   The data definition.
   * @param string $name
   *   The name of the created property.
   * @param \Drupal\plugin\Plugin\Field\FieldType\PluginCollectionItemInterface $parent
   *   The parent object of the data property.
   */
  public function __construct(DataDefinitionInterface $definition, $name, PluginCollectionItemInterface $parent) {
    parent::__construct($definition, $name, $parent);
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($value, $notify = TRUE) {
    $value = (array) $value;
    $plugin_instance = $this->parent->getContainedPluginInstance();
    if ($plugin_instance instanceof ConfigurablePluginInterface) {
      $plugin_instance->setConfiguration($value);
    }
    $this->parent->onChange($this->getName());
  }

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    $plugin_instance = $this->parent->getContainedPluginInstance();
    if ($plugin_instance instanceof ConfigurablePluginInterface) {
      return $plugin_instance->getConfiguration();
    }
    return [];
  }

}
