<?php

namespace Drupal\openy_digital_signage_screen;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Modifies the IPE Access manager service.
 */
class OpenyDigitalSignageScreenServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Overrides plugin.manager.ipe_access class to disable panelizer on a screen page.
    $definition = $container->getDefinition('plugin.manager.ipe_access');
    $definition->setClass('Drupal\openy_digital_signage_screen\Plugin\OpenYScreenIPEAccessManager');
  }

}
