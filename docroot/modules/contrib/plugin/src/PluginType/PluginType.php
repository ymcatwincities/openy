<?php

namespace Drupal\plugin\PluginType;

use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\plugin\PluginDefinition\PluginDefinitionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a plugin type.
 */
class PluginType implements ConfigurablePluginTypeInterface {

  use DependencySerializationTrait;

  /**
   * The ID of the configuration schema of plugins of this type.
   *
   * @var string
   *   A configuration schema ID. It may contain the tokens "[plugin_type_id|
   *   and "[plugin_id]", which will be replaced by the plugin type ID and
   *   plugin ID respectively.
   *
   * @see self::getPluginConfigurationSchemaId()
   */
  protected $configurationSchemaId = 'plugin.plugin_configuration.[plugin_type_id].[plugin_id]';

  /**
   * The service container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   *
   * @todo Make this class a real value object in the next major version.
   */
  protected $container;

  /**
   * The ID.
   *
   * @var string
   */
  protected $id;

  /**
   * Whether this plugin type can be used as a field type.
   *
   * @var bool
   */
  protected $fieldType = TRUE;

  /**
   * The human-readable label.
   *
   * @var \Drupal\Core\StringTranslation\TranslatableMarkup|string
   */
  protected $label;

  /**
   * The human-readable description.
   *
   * @var \Drupal\Core\StringTranslation\TranslatableMarkup|string|null
   */
  protected $description;

  /**
   * The operations provider..
   *
   * @var \Drupal\plugin\PluginType\PluginTypeOperationsProviderInterface
   */
  protected $operationsProvider;

  /**
   * The plugin definition decorator class.
   *
   * @var string|null
   *   A class that implements
   *   \Drupal\plugin\PluginDefinition\PluginDefinitionDecoratorInterface or
   *   NULL if definitions of plugins of this type do not have to be decorated
   *   (e.g. already implement
   *   \Drupal\plugin\PluginDefinition\PluginDefinitionInterface).
   */
  protected $pluginDefinitionDecoratorClass;

  /**
   * The plugin type provider.
   *
   * @var string
   *   The provider is the machine name of the module that provides the plugin
   *   type.
   */
  protected $provider;

  /**
   * The plugin manager service ID.
   *
   * @var string
   */
  protected $pluginManagerServiceId;

  /**
   * Constructs a new instance.
   *
   * @param mixed[] $definition
   *   The plugin type definition.
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translator.
   * @param \Drupal\Core\DependencyInjection\ClassResolverInterface $class_resolver
   *   The class resolver.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config_manager
   *   The typed configuration manager.
   *
   * @param mixed[] $definition
   *
   * @internal
   */
  public function __construct(array $definition, ContainerInterface $container, TranslationInterface $string_translation, ClassResolverInterface $class_resolver, TypedConfigManagerInterface $typed_config_manager) {
    if (!is_string($definition['id']) || !strlen($definition['id'])) {
      throw new \InvalidArgumentException(sprintf('The plugin type definition ID must be a non-empty string, but %s was given.', gettype($definition['id'])));
    }
    $this->id = $definition['id'];
    $this->label = $definition['label'] = new TranslatableMarkup($definition['label'], [], [], $string_translation);
    $this->description = $definition['description'] = isset($definition['description']) ? new TranslatableMarkup($definition['description'], [], [], $string_translation) : NULL;
    if (array_key_exists('field_type', $definition)) {
      if (!is_bool($definition['field_type'])) {
        throw new \InvalidArgumentException(sprintf('The plugin type definition "field_type" item must be a boolean, but %s was given.', gettype($definition['field_type'])));
      }
      $this->fieldType = $definition['field_type'];
    }
    if (array_key_exists('plugin_definition_decorator_class', $definition)) {
      $class = $definition['plugin_definition_decorator_class'];
      if (!class_exists($class)) {
        $type = is_scalar($class) ? $class : gettype($class);
        throw new \InvalidArgumentException(sprintf('The plugin type definition "plugin_definition_decorator_class" item must valid class name, but "%s" was given and it does not exist.', $type));
      }
      $this->pluginDefinitionDecoratorClass = $definition['plugin_definition_decorator_class'];
    }
    if (isset($definition['plugin_configuration_schema_id'])) {
      if (!is_string($definition['plugin_configuration_schema_id'])) {
        throw new \InvalidArgumentException(sprintf('The plugin type definition "plugin_configuration_schema_id" item must be a string, but %s was given.', gettype($definition['field_type'])));
      }
      $this->configurationSchemaId = $definition['plugin_configuration_schema_id'];
    }
    $plugin_configuration_schema_id = $this->getPluginConfigurationSchemaId('*');
    if (!$typed_config_manager->hasConfigSchema($plugin_configuration_schema_id)) {
      throw new \InvalidArgumentException(sprintf('The plugin type definition "plugin_configuration_schema_id" item references the configuration schema "%s" ("%s"), which does not exist.', $plugin_configuration_schema_id, $this->configurationSchemaId));
    }
    $operations_provider_class = array_key_exists('operations_provider_class', $definition) ? $definition['operations_provider_class'] : DefaultPluginTypeOperationsProvider::class;
    $this->operationsProvider = $class_resolver->getInstanceFromDefinition($operations_provider_class);
    $this->pluginManagerServiceId = $definition['plugin_manager_service_id'];
    $this->provider = $definition['provider'];
    $this->container = $container;
  }

  /**
   * {@inheritdoc}
   */
  public static function createFromDefinition(ContainerInterface $container, array $definition) {
    return new static($definition, $container, $container->get('string_translation'), $container->get('class_resolver'), $container->get('config.typed'));
  }

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->label;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function getProvider() {
    return $this->provider;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginManager() {
    return $this->container->get($this->pluginManagerServiceId);
  }

  /**
   * {@inheritdoc}
   */
  public function ensureTypedPluginDefinition($plugin_definition) {
    if ($this->pluginDefinitionDecoratorClass && !($plugin_definition instanceof $this->pluginDefinitionDecoratorClass)) {
      $plugin_definition_decorator_class = $this->pluginDefinitionDecoratorClass;
      return $plugin_definition_decorator_class::createFromDecoratedDefinition($plugin_definition);
    }
    elseif ($plugin_definition instanceof PluginDefinitionInterface) {
      return $plugin_definition;
    }
    else {
      throw new \Exception(sprintf('A plugin definition of plugin type %s does not implement required %s, but its type also does not specify a plugin definition decorator.', $this->getId(), PluginDefinitionInterface::class));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getOperationsProvider() {
    return $this->operationsProvider;
  }

  /**
   * {@inheritdoc}
   */
  public function isFieldType() {
    return $this->fieldType;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginConfigurationSchemaId($plugin_id) {
    return str_replace(['[plugin_type_id]', '[plugin_id]'], [$this->id, $plugin_id], $this->configurationSchemaId);
  }

}
