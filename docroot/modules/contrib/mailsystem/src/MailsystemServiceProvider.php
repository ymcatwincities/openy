<?php

/**
 * @file
 * Contains \Drupal\mailsystem\MailsystemServiceProvider.
 */

namespace Drupal\mailsystem;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Defines the Mailsystem service provider.
 */
class MailsystemServiceProvider implements ServiceProviderInterface, ServiceModifierInterface {

 /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
  }

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Overrides mail-factory class to use our own mail manager.
    $container->getDefinition('plugin.manager.mail')
      ->setClass('Drupal\mailsystem\MailsystemManager')
      ->addMethodCall('setThemeManager', [new Reference('theme.manager')])
      ->addMethodCall('setThemeInitialization', [new Reference('theme.initialization')]);
  }

}
