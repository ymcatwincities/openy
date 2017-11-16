<?php

namespace Drupal\fullcalendar\Plugin;

use Drupal\Core\Plugin\DefaultLazyPluginCollection;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\views\Plugin\views\style\StylePluginBase;

/**
 * @todo.
 */
class FullcalendarPluginCollection extends DefaultLazyPluginCollection {

  /**
   * The manager used to instantiate the plugins.
   *
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $manager;

  /**
   * @todo.
   *
   * @var \Drupal\views\Plugin\views\style\StylePluginBase
   */
  protected $style;

  /**
   * Constructs a FullcalendarPluginCollection object.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $manager
   *   The manager to be used for instantiating plugins.
   * @param \Drupal\views\Plugin\views\style\StylePluginBase $style
   *   The style plugin that contains these plugins.
   */
  public function __construct(PluginManagerInterface $manager, StylePluginBase $style) {
    $this->manager = $manager;
    $this->style = $style;

    // Store all display IDs to access them easy and fast.
    $instance_ids = array_keys($this->manager->getDefinitions());
    $this->instanceIDs = array_combine($instance_ids, $instance_ids);

    parent::__construct($manager, $this->instanceIDs);
  }

  /**
   * {@inheritdoc}
   */
  protected function initializePlugin($plugin_id) {
    if (isset($this->pluginInstances[$plugin_id])) {
      return;
    }

    $this->pluginInstances[$plugin_id] = $this->manager->createInstance($plugin_id, array(), $this->style);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration($configuration) {
    return $this;
  }

}
