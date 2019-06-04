<?php

namespace Drupal\openy_socrates;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Openy Socrates Compiler Pass.
 */
class OpenySocratesCompilerPass implements CompilerPassInterface {

  const OPENYINTERFACE = 'Drupal\openy_socrates\OpenyDataServiceInterface';
  const OPENY_CRON_INTERFACE = 'Drupal\openy_socrates\OpenyCronServiceInterface';

  /**
   * {@inheritdoc}
   */
  public function process(ContainerBuilder $container) {
    // TODO: Implement process() method.
    $definition = $container->getDefinition('socrates');
    $dds = [];
    // Retrieve registered OpenY Data Services from the container.
    // @see openy_socrates.services.yml for example of tags usages.
    $openyds = $container->findTaggedServiceIds(
      'openy_data_service'
    );
    foreach ($openyds as $id => $attributes) {
      $dsdefinition = $container->getDefinition($id);
      $dsclass = $dsdefinition->getClass();
      if (!in_array(self::OPENYINTERFACE, class_implements($dsclass))) {
        throw new OpenySocratesException(
          "Service $id should implement OpenyDataServiceInterface"
        );
      }
      $priority = isset($attributes[0]['priority']) ? $attributes[0]['priority'] : 0;
      $dds[0][$priority][] = new Reference($id);
    }

    $definition->addMethodCall('collectDataServices', $dds);

    // Cron implementation.
    $openy_cron_services = $container->findTaggedServiceIds('openy_cron_service');

    $openy_cron_service_instances = [];
    foreach ($openy_cron_services as $id => $attributes) {
      $cs_definition = $container->getDefinition($id);
      $cs_class = $cs_definition->getClass();
      if (!in_array(self::OPENY_CRON_INTERFACE, class_implements($cs_class))) {
        throw new OpenySocratesException(
          "Service $id should implement OpenyCronServiceInterface"
        );
      }

      $periodicity = isset($attributes[0]['periodicity']) ? $attributes[0]['periodicity'] : 0;
      $openy_cron_service_instances[0][$periodicity][] = new Reference($id);
    }

    $definition->addMethodCall('collectCronServices', $openy_cron_service_instances);
  }

}
