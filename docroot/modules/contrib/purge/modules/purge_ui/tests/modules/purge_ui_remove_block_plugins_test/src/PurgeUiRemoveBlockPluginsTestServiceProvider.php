<?php

namespace Drupal\purge_ui_remove_block_plugins_test;

use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;

/**
 * Replaces the queuers and processors plugin managers with failing stubs.
 */
class PurgeUiRemoveBlockPluginsTestServiceProvider implements ServiceModifierInterface {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $purge_queuer = $container->getDefinition('plugin.manager.purge.queuer');
    $purge_queuer->setClass('Drupal\purge_ui_remove_block_plugins_test\BlackholePluginManager');
    $purge_processor = $container->getDefinition('plugin.manager.purge.processor');
    $purge_processor->setClass('Drupal\purge_ui_remove_block_plugins_test\BlackholePluginManager');
  }

}
