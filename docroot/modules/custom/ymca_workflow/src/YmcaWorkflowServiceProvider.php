<?php

namespace Drupal\ymca_workflow;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Override default paramconverter.
 */
class YmcaWorkflowServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Overrides language_manager class to test domain language negotiation.
    $definition = $container->getDefinition('paramconverter.entity');
    $definition->setClass('Drupal\ymca_workflow\YmcaWorkflowEntityConverter');

    // Overrides workflow.manager class to add/overwite workflow logic.
    $definition = $container->getDefinition('workflow.manager');
    $definition->setClass('Drupal\ymca_workflow\YmcaWorkflowManager');
  }

}
