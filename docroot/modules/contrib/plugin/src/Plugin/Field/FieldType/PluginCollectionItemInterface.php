<?php

namespace Drupal\plugin\Plugin\Field\FieldType;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Field\FieldItemInterface;

/**
 * Defines a plugin collection field item.
 */
interface PluginCollectionItemInterface extends FieldItemInterface {

  /**
   * Returns the type of the plugins contained by this item.
   *
   * @return \Drupal\plugin\PluginType\PluginTypeInterface
   */
  public function getPluginType();

  /**
   * Validates a plugin instance.
   *
   * @param \Drupal\Component\Plugin\PluginInspectionInterface $plugin_instance
   *
   * @throws \Exception
   *
   * @deprecated Deprecated as of 8.x-2.0 and scheduled for removal before
   *   8.x-3.0. Use static::getPluginType()->getPluginManager()->hasDefinition()
   *   instead.
   */
  public function validatePluginInstance(PluginInspectionInterface $plugin_instance);

  /**
   * Creates a plugin instance.
   *
   * @param string $plugin_id
   * @param mixed[] $plugin_configuration
   *
   * @return \Drupal\Component\Plugin\PluginInspectionInterface|null
   *   A plugin instance or NULL if there was no plugin ID.
   *
   * @deprecated Deprecated as of 8.x-2.0 and scheduled for removal before
   *   8.x-3.0. Use
   *   static::getPluginType()->getPluginManager()->createInstance() instead.
   */
  public function createContainedPluginInstance($plugin_id, array $plugin_configuration = []);

  /**
   * Gets the instantiated plugin.
   *
   * @return \Drupal\Component\Plugin\PluginInspectionInterface|null
   *   The plugin or NULL if no plugin was set yet.
   */
  public function getContainedPluginInstance();

  /**
   * Sets the instantiated plugin.
   *
   * @param \Drupal\Component\Plugin\PluginInspectionInterface $plugin_instance
   *
   * @return $this
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown if the given plugin instance does not exist for the type of plugin
   *   this container contains.
   */
  public function setContainedPluginInstance(PluginInspectionInterface $plugin_instance);

  /**
   * Resets the instantiated plugin.
   *
   * @return $this
   */
  public function resetContainedPluginInstance();

  /**
   * Gets the plugin ID.
   *
   * @return string
   *
   * @deprecated Deprecated as of 8.x-2.0 and scheduled for removal before
   *   8.x-3.0. Use static::getContainedPluginInstance()->getPluginId() instead.
   */
  public function getContainedPluginId();

  /**
   * Sets the plugin ID.
   *
   * @param string $plugin_id
   *
   * @return $this
   *
   * @deprecated Deprecated as of 8.x-2.0 and scheduled for removal before
   *   8.x-3.0. Use static::setContainedPluginInstance().
   */
  public function setContainedPluginId($plugin_id);

  /**
   * Sets the plugin configuration.
   *
   * @return mixed[]
   *
   * @deprecated Deprecated as of 8.x-2.0 and scheduled for removal before
   *   8.x-3.0. Use static::getContainedPluginInstance()->getConfiguration()
   *   instead.
   */
  public function getContainedPluginConfiguration();

  /**
   * Sets the plugin configuration.
   *
   * @param mixed[] $plugin_configuration
   *
   * @return $this
   *
   * @deprecated Deprecated as of 8.x-2.0 and scheduled for removal before
   *   8.x-3.0. Use static::getContainedPluginInstance()->setConfiguration()
   *   instead.
   */
  public function setContainedPluginConfiguration(array $plugin_configuration);

}
