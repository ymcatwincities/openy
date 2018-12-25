<?php

namespace Drupal\plugin\Plugin;

/**
 * Implements \Drupal\plugin\PluginOperationsProviderProviderInterface for plugin managers.
 *
 * Classes using this trait MUST implement
 * \Drupal\Component\Plugin\Discovery\DiscoveryInterface and SHOULD implement
 * \Drupal\plugin\PluginOperationsProviderProviderInterface.
 *
 * @deprecated Deprecated as of Plugin 8.x-2.0-rc2. Scheduled for removal before
 *   8.x-3.0.
 *   It is impossible to reliably inspect array plugin definitions. Instead of
 *   this trait, follow these steps:
 *   1) Use
 *      \Drupal\plugin\PluginType\PluginTypeInterface::ensureTypedDefinition()
 *      to make sure the plugin definition is an object.
 *   2) Check if the plugin definition implements
 *      \Drupal\plugin\PluginDefinition\PluginOperationsProviderDefinitionInterface.
 *   3) If that is true, call ::getOperationsProviderClass() on the definition.
 *   4) Instantiate the operations provider class using
 *      \Drupal\Core\DependencyInjection\ClassResolverInterface::getInstanceFromDefinition().
 */
trait PluginOperationsProviderPluginManagerTrait {

  /**
   * The class resolver.
   *
   * @var \Drupal\Core\DependencyInjection\ClassResolverInterface
   */
  protected $classResolver;

  /**
   * {@inheritdoc}
   */
  public function getOperationsProvider($plugin_id) {
    /** @var \Drupal\Component\Plugin\Discovery\DiscoveryInterface|\Drupal\plugin\Plugin\PluginOperationsProviderPluginManagerTrait $this */
    $definition = $this->getDefinition($plugin_id);
    if (isset($definition['operations_provider'])) {
      return $this->classResolver->getInstanceFromDefinition($definition['operations_provider']);
    }
    return NULL;
  }

}
