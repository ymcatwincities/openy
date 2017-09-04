<?php

namespace Drupal\lazyloader\Hooks;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

class ThemeRegistryAlter {

  /** @var \Drupal\Core\Extension\ModuleHandlerInterface */
  protected $moduleHandler;

  /**
   * Creates a new ThemeRegistryAlter instance.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   The module handler.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   */
  public function __construct(ModuleHandlerInterface $moduleHandler, ConfigFactoryInterface $config) {
    $this->moduleHandler = $moduleHandler;
    $this->config = $config;
  }

  public function themeRegistryAlter(&$theme_registry) {
    if ($this->config->get('lazyloader.configuration')->get('enabled')) {
      $theme_registry['image']['path'] = $this->moduleHandler->getModule('lazyloader')->getPath() . '/templates';
      $theme_registry['image']['template'] = 'lazyloader-image';

      $theme_registry['responsive_image']['preprocess functions'][] = 'lazyloader_preprocess_responsive_image';
    }
  }

}
