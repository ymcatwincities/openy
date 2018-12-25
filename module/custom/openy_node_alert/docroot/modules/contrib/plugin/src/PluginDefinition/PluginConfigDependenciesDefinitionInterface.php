<?php

namespace Drupal\plugin\PluginDefinition;

/**
 * Defines a plugin definition that includes config dependencies.
 *
 * @ingroup Plugin
 */
interface PluginConfigDependenciesDefinitionInterface extends PluginDefinitionInterface {

  /**
   * Sets the dependencies.
   *
   * @param array[] $dependencies.
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
  public function setConfigDependencies(array $dependencies);

  /**
   * Gets the dependencies.
   *
   * @return array[]
   *   See self::setConfigDependencies().
   */
  public function getConfigDependencies();

}
