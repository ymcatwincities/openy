<?php

namespace Drupal\panelizer\Plugin;

use Drupal\Component\Plugin\Discovery\CachedDiscoveryInterface;
use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Interface for Panelizer entity plugin manager.
 */
interface PanelizerEntityManagerInterface extends PluginManagerInterface, CachedDiscoveryInterface {

}