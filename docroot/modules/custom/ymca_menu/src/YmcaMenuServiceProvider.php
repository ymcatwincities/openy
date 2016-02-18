<?php

namespace Drupal\ymca_menu;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Alter the service container to use a custom class.
 */
class YmcaMenuServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $definition = $container->getDefinition('menu.active_trail');

    // Use YmcaMenuServiceProvider class instead of the
    // default MenuActiveTrail class.
    $definition->setClass('Drupal\ymca_menu\YmcaMenuActiveTrail');
  }

}
