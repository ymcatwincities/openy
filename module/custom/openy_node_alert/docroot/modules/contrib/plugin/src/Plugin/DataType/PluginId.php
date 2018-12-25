<?php

namespace Drupal\plugin\Plugin\DataType;

use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\Plugin\DataType\StringData;
use Drupal\plugin\Plugin\Field\FieldType\PluginCollectionItemInterface;

/**
 * Provides a plugin ID data type.
 *
 * @DataType(
 *   id = "plugin_id",
 *   label = @Translation("Plugin ID")
 * )
 */
class PluginId extends StringData {

  /**
   * The parent typed data object.
   *
   * @var \Drupal\plugin\Plugin\Field\FieldType\PluginCollectionItemInterface
   */
  protected $parent;

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
    $value = (string) $value;
    $plugin_instance = $this->parent->getContainedPluginInstance();
    if (!$value) {
      $this->parent->resetContainedPluginInstance();
    }
    elseif (!$plugin_instance || $plugin_instance->getPluginId() != $value) {
      $plugin_instance = $this->parent->getPluginType()->getPluginManager()->createInstance($value);
      $this->parent->setContainedPluginInstance($plugin_instance);
    }
    $this->parent->onChange($this->getName());
  }

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    $plugin_instance = $this->parent->getContainedPluginInstance();
    if ($plugin_instance) {
      return $plugin_instance->getPluginId();
    }
  }

}
