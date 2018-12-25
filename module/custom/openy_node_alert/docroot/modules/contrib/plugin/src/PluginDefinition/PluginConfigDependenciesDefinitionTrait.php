<?php

namespace Drupal\plugin\PluginDefinition;

/**
 * Implements \Drupal\Core\Plugin\PluginConfigDependenciesDefinitionInterface.
 *
 * @ingroup Plugin
 */
trait PluginConfigDependenciesDefinitionTrait {

  /**
   * The dependencies.
   *
   * @var array[]
   *   An array of dependencies keyed by the type of dependency. One example:
   *   @code
   *   array(
   *     'module' => array(
   *       'node',
   *       'field',
   *       'image',
   *     ),
   *   );
   *   @endcode
   *
   * @return $this
   */
  protected $configDependencies = [];

  /**
   * Implements \Drupal\Core\Plugin\PluginConfigDependenciesDefinitionInterface::setConfigDependencies().
   */
  public function setConfigDependencies(array $dependencies) {
    $this->configDependencies = $dependencies;

    return $this;
  }

  /**
   * Implements \Drupal\Core\Plugin\PluginConfigDependenciesDefinitionInterface::getConfigDependencies().
   */
  public function getConfigDependencies() {
    return $this->configDependencies;
  }

}
