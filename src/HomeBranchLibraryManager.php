<?php

namespace Drupal\openy_home_branch;

use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Defines the base plugin for HomeBranchLibrary classes.
 *
 * @see \Drupal\openy_home_branch\HomeBranchLibraryInterface
 * @see \Drupal\openy_home_branch\Annotation\HomeBranchLibrary
 * @see plugin_api
 */
class HomeBranchLibraryManager extends DefaultPluginManager {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/HomeBranchLibrary',
      $namespaces,
      $module_handler,
      'Drupal\openy_home_branch\HomeBranchLibraryInterface',
      'Drupal\openy_home_branch\Annotation\HomeBranchLibrary'
    );

    $this->alterInfo('home_branch_library_info');
    $this->setCacheBackend($cache_backend, 'home_branch_library');
    $this->factory = new DefaultFactory($this->getDiscovery());
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitionsByEntityType($entity) {
    $definitions = $this->getDefinitions();
    foreach ($definitions as $id => $definition) {
      if ($definition['entity'] != $entity) {
        unset($definitions[$id]);
      }
    }
    return $definitions;
  }

}
