<?php

namespace Drupal\sitemap;

use Drupal\Core\DependencyInjection\ServiceModifierInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;

/**
 * ServiceProvider class for sitemap module.
 */
class SitemapServiceProvider implements ServiceModifierInterface {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Change class of menu.link_tree service.
    $definition = $container->getDefinition('menu.link_tree');
    $definition->setClass('Drupal\sitemap\Menu\MenuLinkTree');
  }

}
