<?php

namespace Drupal\plugin\PluginDiscovery;

/**
 * Provides a filtered plugin manager.
 */
class LimitedPluginDiscoveryDecorator extends PluginDiscoveryDecorator implements LimitedPluginDiscoveryInterface {

  /**
   * The discovery limit.
   *
   * @var string[]|null
   *   An array of plugin IDs or NULL if the limit is not set.
   */
  protected $discoveryLimit;

  /**
   * {@inheritdoc}
   */
  protected function processDecoratedDefinitions(array $decorated_definitions) {
    if (is_array($this->discoveryLimit)) {
      return array_intersect_key($decorated_definitions, array_flip($this->discoveryLimit));
    }
    else {
      return $decorated_definitions;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setDiscoveryLimit(array $plugin_ids) {
    $this->discoveryLimit = $plugin_ids;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function resetDiscoveryLimit() {
    $this->discoveryLimit = NULL;
    $this->clearCachedDefinitions();

    return $this;
  }

}
