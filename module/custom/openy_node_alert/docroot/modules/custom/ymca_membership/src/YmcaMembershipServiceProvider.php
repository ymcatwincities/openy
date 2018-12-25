<?php

namespace Drupal\ymca_membership;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Alter the service container to use a custom class.
 */
class YmcaMembershipServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $definition = $container->getDefinition('contact.mail_handler');

    // Use YmcaMenuServiceProvider class instead of the
    // default MenuActiveTrail class.
    $definition->setClass('Drupal\ymca_membership\YmcaMembershipMailHandler');
  }

}
