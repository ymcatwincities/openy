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
      ->addArgument(new Reference('theme.manager'))
      ->addArgument(new Reference('theme.initialization'))
      ->addArgument(new Reference('theme.registry'))
      ->addArgument(new Reference('mailsystem.theme.registry'));
  }

}
