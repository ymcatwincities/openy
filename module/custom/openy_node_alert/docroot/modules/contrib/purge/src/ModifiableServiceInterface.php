<?php

namespace Drupal\purge;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\purge\ServiceInterface;

/**
 * Describes a container service of which its back-end plugins can be changed.
 */
interface ModifiableServiceInterface {

  /**
   * Retrieve the plugin IDs of plugins that can be enabled.
   *
   * @see \Drupal\purge\ModifiableServiceInterface::setPluginsEnabled().
   *
   * @return string[]
   *   Unassociative array with plugin IDs that are available to be enabled.
   */
  public function getPluginsAvailable();

  /**
   * Set the plugins used by the service and reload it.
   *
   * @param string[] $plugin_ids
   *   Unassociative array with plugin IDs to be enabled.
   *
   * @see \Drupal\purge\ModifiableServiceInterface::getPluginsAvailable().
   *
   * @return void
   */
  public function setPluginsEnabled(array $plugin_ids);

}
