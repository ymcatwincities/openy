<?php

namespace Drupal\purge;

/**
 * Adds implementations to \Drupal\purge\ModifiableServiceInterface derivatives.
 */
trait ModifiableServiceBaseTrait {

  /**
   * @see \Drupal\purge\ModifiableServiceInterface::getPluginsAvailable().
   */
  public function getPluginsAvailable() {
    $enabled = $this->getPluginsEnabled();
    $available = [];
    foreach ($this->getPlugins() as $plugin_id => $definition) {
      if (!in_array($plugin_id, $enabled)) {
        $available[] = $plugin_id;
      }
    }
    return $available;
  }

}
