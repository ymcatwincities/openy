<?php
/**
 * @file
 * Contains \Drupal\file_entity\FileEntityServiceProvider.
 */

namespace Drupal\file_entity;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Service Provider for File entity.
 */
class FileEntityServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $modules = $container->getParameter('container.modules');
    if (isset($modules['rest'])) {
      // Add a normalizer service for file entities.
      $service_definition = new Definition('Drupal\file_entity\Normalizer\FileEntityNormalizer', array(
        new Reference('rest.link_manager'),
        new Reference('entity.manager'),
        new Reference('module_handler'),
      ));
      // The priority must be higher than that of
      // serializer.normalizer.file_entity.hal in hal.services.yml
      $service_definition->addTag('normalizer', array('priority' => 30));
      $container->setDefinition('serializer.normalizer.entity.file_entity', $service_definition);

      // Add a normalizer service for file fields.
      $service_definition = new Definition('Drupal\file_entity\Normalizer\FileItemNormalizer', array(
        new Reference('rest.link_manager'),
        new Reference('serializer.entity_resolver'),
      ));
      // Supersede EntityReferenceItemNormalizer.
      $service_definition->addTag('normalizer', array('priority' => 20));
      $container->setDefinition('serializer.normalizer.entity_reference_item.file_entity', $service_definition);
    }
  }
}
