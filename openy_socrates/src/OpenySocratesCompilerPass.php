<?php


namespace Drupal\openy_socrates;


use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class OpenySocratesCompilerPass implements CompilerPassInterface {

  const OPENYINTERFACE = 'Drupal\openy_socrates\OpenyDataServiceInterface';

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
   *
   * @throws \Drupal\openy_socrates\OpenySocratesException
   */
  public function process(ContainerBuilder $container) {
    // TODO: Implement process() method.
    $definition = $container->getDefinition('socrates');
    $dds = array();
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
      $dds['priorities'][$priority][] = new Reference($id);
    }

    $definition->addMethodCall('collectDataServices', $dds);
  }
}
