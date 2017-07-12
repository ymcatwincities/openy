<?php

namespace Drupal\default_content;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Drupal\default_content\Normalizer\TermEntityNormalizer;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Adds customized normalizer to handle taxonomy hierarchy.
 */
class DefaultContentServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $modules = $container->getParameter('container.modules');
    // @todo Get rid of after https://www.drupal.org/node/2543726
    if (isset($modules['taxonomy'])) {
      // Add a normalizer service for term entities.
      $service_definition = new Definition(TermEntityNormalizer::class, [
        new Reference('hal.link_manager'),
        new Reference('entity.manager'),
        new Reference('module_handler'),
      ]);
      // The priority must be higher than that of
      // serializer.normalizer.entity.hal in hal.services.yml.
      $service_definition->addTag('normalizer', ['priority' => 30]);
      $container->setDefinition('default_content.normalizer.taxonomy_term.halt', $service_definition);
    }
  }

}
