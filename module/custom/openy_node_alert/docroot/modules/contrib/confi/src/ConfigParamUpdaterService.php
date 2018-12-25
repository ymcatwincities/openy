<?php

namespace Drupal\config_import;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class ConfigParamUpdaterService.
 */
class ConfigParamUpdaterService {

  use StringTranslationTrait;

  /**
   * ConfigManager definition.
   *
   * @var ConfigManagerInterface
   */
  protected $configManager;
  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * ConfigImporterService constructor.
   *
   * @param ConfigManagerInterface $config_manager
   *   ConfigManager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(
    ConfigManagerInterface $config_manager,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->configManager = $config_manager;
    $this->logger = $logger_factory->get('config_update');
  }

  /**
   * Update configuration param from existing config file.
   *
   * @param string $config
   *   Config full name with path.
   *   Example:
   *   drupal_get_path('module', 'test') . '/config/install/test.config.yml'.
   * @param string $config_name
   *   Config name.
   *   Example: "views.view.who_s_online".
   * @param string $param
   *   Identifier to store value in configuration.
   *   Example: "dependencies.module".
   */
  public function update($config, $config_name, $param) {
    // Get base storage config.
    if (!file_exists($config)) {
      $this->logger->error($this->t('File @file does not exist.', ['@file' => $config]));
      return;
    }
    $storage_config = Yaml::decode(file_get_contents($config));
    // Retrieve a value from a nested array with variable depth.
    $update_value = NestedArray::getValue($storage_config, explode('.', $param));
    if (!$update_value) {
      $this->logger->info(
        $this->t('Param "@param" not exist in config @name.',
        ['@name' => $config_name, '@param' => $param])
      );
      return;
    }
    // Get active storage config.
    $config_factory = $this->configManager->getConfigFactory();
    $config = $config_factory->getEditable($config_name);
    if ($config->isNew() && empty($config->getOriginal())) {
      $this->logger->error($this->t('Config @name does not exist.', ['@name' => $config_name]));
      return;
    }
    // Update value retrieved from storage config.
    $config->set($param, $update_value);
    $config->save();
    $this->logger->info($this->t('Param "@param" in config @name was updated.',
      [
        '@name' => $config_name,
        '@param' => $param,
      ]
    ));
  }

}
