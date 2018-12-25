<?php

namespace Drupal\config_import;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Class ConfigImportServiceProvider.
 */
class ConfigImportServiceProvider extends ServiceProviderBase {

  /**
   * DI container.
   *
   * @var ContainerBuilder
   */
  private $container;

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    $this->container = $container;
    $this->registerFeaturesImporter();
  }

  /**
   * Register features importer service if dependencies installed.
   *
   * @see ConfigFeaturesImporterService::create()
   */
  protected function registerFeaturesImporter() {
    $this->registerService('config_import.features_importer', ConfigFeaturesImporterService::class, [
      'config_update.config_update',
      'features.manager',
      'features_assigner',
    ], [
      'logger.factory',
    ]);
  }

  /**
   * Register service if required dependencies are available within container.
   *
   * @param string $id
   *   Service ID.
   * @param string $class
   *   FQN of service class.
   * @param string[] $required
   *   List of required dependency names.
   * @param string[] $optional
   *   List of optional dependency names.
   */
  protected function registerService($id, $class, array $required, array $optional = []) {
    // All required services must be available within container.
    if (count(array_filter(array_map([$this->container, 'has'], $required))) === count($required)) {
      $this->container->register($id, $class)->setArguments(array_map(
        [$this->container, 'getDefinition'],
        array_merge($required, $optional)
      ));
    }
  }

}
