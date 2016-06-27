<?php

namespace Drupal\ymca_mindbody;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\State\State;

/**
 * Class YmcaMindbodyRequestGuard.
 * 
 * @package Drupal\ymca_mindbody
 */
class YmcaMindbodyRequestGuard implements YmcaMindbodyRequestGuardInterface {

  /**
   * State definition.
   *
   * @var State
   */
  protected $state;
  
  /**
   * Config Factory definition.
   *
   * @var ConfigFactory
   */
  protected $configFactory;
  
  /**
   * Constructor.
   */
  public function __construct(State $state, ConfigFactory $config_factory) {
    $this->state = $state;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function status() {
    $cache_state = $this->state->get('mindbody_cache_proxy');
    $settings = $this->configFactory->get('ymca_mindbody.settings');
    if (isset($cache_state->miss) && $cache_state->miss >= $settings->get('max_requests')) {
      return FALSE;
    }
    return TRUE;
  }

}
