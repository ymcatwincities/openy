<?php

/**
 * @file
 * Contains \Drupal\config_inspector\ConfigInspectorManager.
 */

namespace Drupal\config_inspector;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Config\Schema\Element;
use Drupal\Core\Config\Schema\SchemaCheckTrait;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Manages plugins for configuration translation mappers.
 */
class ConfigInspectorManager extends DefaultPluginManager {

  use SchemaCheckTrait;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $configFactory;

  /**
   * The typed configuration manager.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface
   */
  protected $typedConfigManager;

  /**
   * Initialize new configuration inspector manager.
   *
   * @param ConfigFactory $config_factory
   *   The configuration factory.
   * @param TypedConfigManagerInterface $typed_config_manager
   *   The typed configuration manager.
   */
  public function __construct(ConfigFactory $config_factory, TypedConfigManagerInterface $typed_config_manager) {
    $this->configFactory = $config_factory;
    $this->typedConfigManager = $typed_config_manager;
  }

  /**
   * Provides definition of a configuration.
   *
   * @param string $plugin_id
   *   A string plugin ID.
   *
   * @return mixed|void
   */
  public function getDefinition($plugin_id, $exception_on_invalid = TRUE) {
    return $this->typedConfigManager->getDefinition($plugin_id, $exception_on_invalid);
  }

  /**
   * Checks if the configuration schema with the given config name exists.
   *
   * @param string $name
   *   Configuration name.
   *
   * @return bool
   *   TRUE if configuration schema exists, FALSE otherwise.
   */
  public function hasSchema($name) {
    return $this->typedConfigManager->hasConfigSchema($name);
  }

  /**
   * Provides configuration data.
   *
   * @param string $name
   *   A string config key.
   *
   * @return array|null
   */
  public function getConfigData($name) {
    return $this->typedConfigManager->get($name)->getValue();
  }

  /**
   * Provides configuration schema.
   *
   * @param string $name
   *   A string config key.
   *
   * @return array|null
   */
  public function getConfigSchema($name) {
    return $this->typedConfigManager->get($name);
  }

  /**
   * Gets all contained typed data properties as plain array.
   *
   * @param array|object $schema
   *   An array of config elements with key.
   *
   * @return array
   *   List of Element objects indexed by full name (keys with dot notation).
   */
  public function convertConfigElementToList($schema) {
    $list = array();
    foreach ($schema as $key => $element) {
      if ($element instanceof Element) {
        $list[$key] = $element;
        foreach ($this->convertConfigElementToList($element) as $sub_key => $value) {
          $list[$key . '.' . $sub_key] = $value;
        }
      }
      else {
        $list[$key] = $element;
      }
    }
    return $list;
  }

  /**
   * Check schema compliance in configuration object.
   *
   * @param $config_name
   *   Configuration name.
   *
   * @throws Drupal\Core\Config\Schema\SchemaIncompleteException
   */
  public function checkValues($config_name) {
    $config_data = \Drupal::config($config_name)->get();
    return $this->checkConfigSchema($this->typedConfigManager, $config_name, $config_data);
  }

}
