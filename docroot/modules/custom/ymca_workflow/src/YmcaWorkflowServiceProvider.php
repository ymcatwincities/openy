<?php

/**
 * @file
 * Contains Drupal\ymca_workflow\YmcaWorkflowServiceProvider
 */

namespace Drupal\ymca_workflow;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

class YmcaWorkflowServiceProvider extends ServiceProviderBase {
  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Overrides language_manager class to test domain language negotiation.
    $definition = $container->getDefinition('paramconverter.entity');
    $definition->setClass('Drupal\ymca_workflow\YmcaWorkflowEntityConverter');
  }
}
