<?php

namespace Drupal\environment_config;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Class EnvironmentConfigService.
 *
 * @package Drupal\config_import
 */
class EnvironmentConfigService implements EnvironmentConfigServiceInterface {

  /**
   * Suffix for environment config file.
   */
  const ENVIRONMENT_CONFIG_SUFFIX = '.env';

  /**
   * Name of the property that is used for indicator.
   */
  const ENVIRONMENT_INDICATOR_NAME = 'active';

  /**
   * Config Factory.
   *
   * @var ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * MindbodyServiceManager constructor.
   *
   * @param ConfigFactoryInterface $config_factory
   *   Config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function getEnvironmentIndicator($config_name) {
    return $this->configFactory
      ->get($this->getEnvironmentConfigFileName($config_name))
      ->get($this::ENVIRONMENT_INDICATOR_NAME);
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveConfig($config_name) {
    return $this->configFactory->get($config_name)->get();
  }

  /**
   * {@inheritdoc}
   */
  public function setActiveConfig($config_name, $env_name) {
    // Set active config.
    $editable = $this->configFactory->getEditable($config_name);
    $env_config = $this->getEnvironmentConfig($config_name, $env_name);

    if (empty($env_config)) {
      return;
    }

    $editable
      ->setData($env_config)
      ->save(TRUE);

    // Update environment indicator.
    $this->configFactory
      ->getEditable($this->getEnvironmentConfigFileName($config_name))
      ->set($this::ENVIRONMENT_INDICATOR_NAME, $env_name)
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  public function getEnvironmentConfig($config_name, $env_name) {
    return $this->configFactory
      ->get($this->getEnvironmentConfigFileName($config_name))
      ->get($env_name);
  }

  /**
   * Returns filename of config file with environment specific settings.
   *
   * @param string $config_name
   *   Configuration name.
   *
   * @return string
   *   Filename of environment specific settings.
   */
  protected function getEnvironmentConfigFileName($config_name) {
    return $config_name . $this::ENVIRONMENT_CONFIG_SUFFIX;
  }

}
