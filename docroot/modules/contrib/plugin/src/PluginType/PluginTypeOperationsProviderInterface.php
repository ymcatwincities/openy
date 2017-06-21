<?php

namespace Drupal\plugin\PluginType;

/**
 * Defines a plugin type operations provider.
 *
 * Classes may also implement any of the following interfaces:
 * - \Drupal\Core\DependencyInjection\ContainerInjectionInterface
 */
interface PluginTypeOperationsProviderInterface {

  /**
   * Gets plugin operations.
   *
   * @param string $plugin_type_id
   *   The ID of the plugin type the operations are for.
   *
   * @return array[]
   *   An array with the same structure as
   *   \Drupal\Core\Entity\EntityListBuilderInterface::getOperations()' return
   *   value.
   */
  public function getOperations($plugin_type_id);

}
