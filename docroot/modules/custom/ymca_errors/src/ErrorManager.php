<?php

namespace Drupal\ymca_errors;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Config\ImmutableConfig;

/**
 * Class ErrorManager.
 */
class ErrorManager {

  /**
   * Config factory.
   *
   * @var ConfigFactory
   */
  protected $configFactory;

  /**
   * Config.
   *
   * @var ImmutableConfig
   */
  protected $config;

  /**
   * ErrorManager constructor.
   *
   * @param ConfigFactory $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactory $config_factory) {
    $this->configFactory = $config_factory;
    $this->config = $this->configFactory->get('ymca_errors.errors');
  }

  /**
   * Return error's text by name.
   *
   * @param string $name
   *   Error name.
   *
   * @return string
   *   Error's text.
   */
  public function getError($name) {
    return $this->config->get($name);
  }

}
