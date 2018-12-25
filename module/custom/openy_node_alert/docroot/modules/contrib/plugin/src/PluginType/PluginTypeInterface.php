<?php

namespace Drupal\plugin\PluginType;

use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a plugin type.
 */
interface PluginTypeInterface {

  /**
   * Creates a plugin type based on a definition.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @param mixed[] $definition
   *
   * @return static
   */
  public static function createFromDefinition(ContainerInterface $container, array $definition);

  /**
   * Gets the ID.
   *
   * @return string
   */
  public function getId();

  /**
   * Gets the human-readable label.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string
   */
  public function getLabel();

  /**
   * Gets the human-readable description.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string
   */
  public function getDescription();

  /**
   * Gets the plugin type provider.
   *
   * @return string
   *   The provider is the machine name of the module that provides the plugin
   *   type.
   */
  public function getProvider();

  /**
   * Gets the plugin manager.
   *
   * @return \Drupal\Component\Plugin\PluginManagerInterface
   */
  public function getPluginManager();

  /**
   * Ensures that a plugin definition is typed.
   *
   * @param \Drupal\plugin\PluginDefinition\PluginDefinitionInterface|mixed $plugin_definition
   *   An original plugin definition of this type. It may already be typed.
   *
   * @return \Drupal\plugin\PluginDefinition\PluginDefinitionInterface
   *   The typed plugin definition.
   *
   * @throws \InvalidArgumentException
   *   Thrown when a typed definition could not be returned.
   */
  public function ensureTypedPluginDefinition($plugin_definition);

  /**
   * Gets the operations provider.
   *
   * @return \Drupal\plugin\PluginType\PluginTypeOperationsProviderInterface
   */
  public function getOperationsProvider();

  /**
   * Gets whether plugin type can be used as a field type.
   *
   * @return bool
   */
  public function isFieldType();

}
