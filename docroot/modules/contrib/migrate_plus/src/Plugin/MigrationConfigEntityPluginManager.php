<?php

/**
 * @file
 * Contains \Drupal\migrate_plus\Plugin\MigrationConfigEntityPluginManager.
 */

namespace Drupal\migrate_plus\Plugin;

use Drupal\Core\Plugin\Discovery\ContainerDerivativeDiscoveryDecorator;
use Drupal\migrate\Plugin\MigrationPluginManager;
use Drupal\migrate_plus\Plugin\Discovery\ConfigEntityDiscovery;

/**
 * Plugin manager for migration plugins.
 */
class MigrationConfigEntityPluginManager extends MigrationPluginManager {

  /**
   * {@inheritdoc}
   */
  protected function getDiscovery() {
    if (!isset($this->discovery)) {
      $discovery = new ConfigEntityDiscovery('migration');
      $this->discovery = new ContainerDerivativeDiscoveryDecorator($discovery);
    }
    return $this->discovery;
  }

}
