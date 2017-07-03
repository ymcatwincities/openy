<?php

namespace Drupal\plugin\Event;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\plugin\PluginType\PluginTypeInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Provides an event that is dispatched when the a default plugin instance is
 * resolved.
 *
 * @see \Drupal\plugin\Event\PluginEvents::RESOLVE_DEFAULT_PLUGIN
 */
class ResolveDefaultPlugin extends Event {

  /**
   * The plugin type.
   *
   * @var \Drupal\plugin\PluginType\PluginTypeInterface
   */
  protected $pluginType;

  /**
   * The default plugin instance.
   *
   * @var \Drupal\Component\Plugin\PluginInspectionInterface|null
   *   The default plugin instance or NULL if there is no default instance.
   */
  protected $defaultPluginInstance;

  /**-
   * Constructs a new instance.
   *
   * @param \Drupal\plugin\PluginType\PluginTypeInterface $plugin_type
   */
  public function __construct(PluginTypeInterface $plugin_type) {
    $this->pluginType = $plugin_type;
  }

  /**
   * Gets the plugin type for which a default plugin instance is resolved.
   *
   * @return \Drupal\plugin\PluginType\PluginTypeInterface
   */
  public function getPluginType() {
    return $this->pluginType;
  }

  /**
   * Gets the default plugin instance.
   *
   * @return \Drupal\Component\Plugin\PluginInspectionInterface|null
   *   The default plugin instance or NULL if there is no default instance.
   */
  public function getDefaultPluginInstance() {
    return $this->defaultPluginInstance;
  }

  /**
   * Sets an the default plugin instance.
   *
   * @param \Drupal\Component\Plugin\PluginInspectionInterface $default_plugin_instance
   *
   * @return $this
   */
  public function setDefaultPluginInstance(PluginInspectionInterface $default_plugin_instance) {
    $this->defaultPluginInstance = $default_plugin_instance;

    return $this;
  }

}
