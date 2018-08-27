<?php

namespace Drupal\openy_repeat;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Modifies the language manager service.
 */
class OpenyRepeatServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Overrides session_instance.manager class so we do not generate
    // session instances but instead use new repeat entity.
    if ($container->has('session_instance.manager')) {
      $definition = $container->getDefinition('session_instance.manager');
      $definition->setClass('Drupal\openy_repeat\RepeatManager');
    }
  }
}
