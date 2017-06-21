<?php

/**
 * @file
 * Contains \Drupal\embed\EmbedTypeInterface.
 */

namespace Drupal\embed\EmbedType;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Provides an interface for an embed type and its metadata.
 *
 * @ingroup embed_api
 */
interface EmbedTypeInterface extends ConfigurablePluginInterface, PluginFormInterface, PluginInspectionInterface {

  /**
   * Gets a configuration value.
   *
   * @param string $name
   *   The name of the plugin configuration value.
   * @param mixed $default
   *   The default value to return if the configuration value does not exist.
   *
   * @return mixed
   *   The currently set configuration value, or the value of $default if the
   *   configuration value is not set.
   */
  public function getConfigurationValue($name, $default = NULL);

  /**
   * Sets a configuration value.
   *
   * @param string $name
   *   The name of the plugin configuration value.
   * @param mixed $value
   *   The value to set.
   */
  public function setConfigurationValue($name, $value);

  /**
   * Gets the default icon URL for the embed type.
   *
   * @return string
   *   The URL to the default icon. Must have been passed through
   *   file_create_url() if the file is local.
   *
   * @see file_create_url()
   */
  public function getDefaultIconUrl();

}
