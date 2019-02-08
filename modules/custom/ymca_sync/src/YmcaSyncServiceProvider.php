<?php

namespace Drupal\ymca_sync;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;
use Drupal\ymca_sync\DependencyInjection\Compiler\YmcaSyncPass;

/**
 * Serialization dependency injection container.
 */
class YmcaSyncServiceProvider implements ServiceProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    $container->addCompilerPass(new YmcaSyncPass());
  }

}
