<?php

namespace Drupal\environment_config;

/**
 * Interface EnvironmentConfigServiceInterface.
 *
 * @package Drupal\environment_config
 */
interface EnvironmentConfigServiceInterface {

  /**
   * Returns active config.
   *
   * @param string $config_name
   *   Configuration name.
   *
   * @return array
   *   Configuration value.
   */
  public function getActiveConfig($config_name);

  /**
   * Set active config basec on environment.
   *
   * @param string $config_name
   *   Configuration name.
   * @param string $env_name
   *   Environment name.
   */
  public function setActiveConfig($config_name, $env_name);

  /**
   * Returns configuration for specific environment.
   *
   * @param string $config_name
   *   Configuration name.
   * @param string $env_name
   *   Environment name.
   *
   * @return array
   *   Configuration value.
   */
  public function getEnvironmentConfig($config_name, $env_name);

  /**
   * Returns active environment name.
   *
   * @param string $config_name
   *   Configuration name.
   *
   * @return array
   *   Environment name.
   */
  public function getEnvironmentIndicator($config_name);

}
