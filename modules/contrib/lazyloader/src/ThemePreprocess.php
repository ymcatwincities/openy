<?php

namespace Drupal\lazyloader;

use Drupal\Core\Config\ConfigFactoryInterface;

class ThemePreprocess {

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Creates a new ThemePreprocess instance.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  public function attachLibrary(array $vars)  {
    $config = $this->configFactory->get('lazyloader.configuration');
    if (!$config->get('enabled')) {
      return $vars;
    }

    $vars['#attached']['library'][] = $this->determineLibraryToAttach();
    return $vars;
  }

  public function addCacheTags(array $vars) {
    $vars['#cache']['tags'][] = 'config:lazyloader.configuration';
    return $vars;
  }

  /**
   * @return string
   */
  private function determineLibraryToAttach() {
    $config = $this->configFactory->get('lazyloader.configuration');

    if ($config->get('debugging')) {
      $library = 'lazyloader/lazysizes';
      return $library;
    }
    elseif ($config->get('cdn') || !file_exists('libraries/lazysizes/lazysizes.min.js')) {
      $library = 'lazyloader/lazysizes-min.cdn';
      return $library;
    }
    else {
      $library = 'lazyloader/lazysizes-min';
      return $library;
    }
  }

}
