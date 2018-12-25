<?php

namespace Drupal\plugin\Controller;

use Drupal\Core\DependencyInjection\ClassResolverInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\plugin\PluginDefinition\PluginDescriptionDefinitionInterface;
use Drupal\plugin\PluginDefinition\PluginLabelDefinitionInterface;
use Drupal\plugin\PluginDefinition\PluginOperationsProviderDefinitionInterface;
use Drupal\plugin\PluginDiscovery\TypedDefinitionEnsuringPluginDiscoveryDecorator;
use Drupal\plugin\PluginType\PluginTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Handles the "list plugin" route.
 */
class ListPlugins extends ListBase {

  /**
   * The class resolver.
   *
   * @var \Drupal\Core\DependencyInjection\ClassResolverInterface
   */
  protected $classResolver;

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translator.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\DependencyInjection\ClassResolverInterface $class_resolver
   *   The class resolver.
   */
  public function __construct(TranslationInterface $string_translation, ModuleHandlerInterface $module_handler, ClassResolverInterface $class_resolver) {
    parent::__construct($string_translation, $module_handler);
    $this->classResolver = $class_resolver;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('string_translation'), $container->get('module_handler'), $container->get('class_resolver'));
  }

  /**
   * Returns the route's title.
   *
   * @param \Drupal\plugin\PluginType\PluginTypeInterface $plugin_type
   *   The plugin type.
   *
   * @return string
   */
  public function title($plugin_type) {
    return $this->t('%label plugins', [
      '%label' => $plugin_type->getLabel(),
    ]);
  }

  /**
   * Handles the route.
   *
   * @param \Drupal\plugin\PluginType\PluginTypeInterface $plugin_type
   *   The plugin type.
   *
   * @return mixed[]|\Symfony\Component\HttpFoundation\Response
   *   A render array or a Symfony response.
   */
  public function execute(PluginTypeInterface $plugin_type) {

    $build = [
      '#empty' => $this->t('There are no available plugins.'),
      '#header' => [$this->t('Plugin'), $this->t('ID'), $this->t('Description'), $this->t('Provider'), $this->t('Operations')],
      '#type' => 'table',
    ];
    $plugin_discovery = new TypedDefinitionEnsuringPluginDiscoveryDecorator($plugin_type);
    /** @var \Drupal\plugin\PluginDefinition\PluginDefinitionInterface[] $plugin_definitions */
    $plugin_definitions = $plugin_discovery->getDefinitions();
    ksort($plugin_definitions);
    foreach ($plugin_definitions as $plugin_definition) {
      $operations = [];
      if ($plugin_definition instanceof PluginOperationsProviderDefinitionInterface) {
        $operations_provider_class = $plugin_definition->getOperationsProviderClass();
        if ($operations_provider_class) {
          /** @var \Drupal\plugin\PluginOperationsProviderInterface $operations_provider */
          $operations_provider = $this->classResolver->getInstanceFromDefinition($operations_provider_class);
          $operations = $operations_provider->getOperations($plugin_definition->getId());
        }
      }
      $build[$plugin_definition->getId()] = [
        'label' => [
          '#markup' => $plugin_definition instanceof PluginLabelDefinitionInterface ? (string) $plugin_definition->getLabel() : NULL,
        ],
        'id' => [
          '#markup' => $plugin_definition->getId(),
          '#prefix' => '<code>',
          '#suffix' => '</code>',
        ],
        'description' => [
          '#markup' => $plugin_definition instanceof PluginDescriptionDefinitionInterface ? (string) $plugin_definition->getDescription() : NULL,
        ],
        'provider' => [
          '#markup' => $this->getProviderLabel($plugin_definition->getProvider()),
        ],
        'operations' => [
          '#links' => $operations,
          '#type' => 'operations',
        ],
      ];
    }

    return $build;
  }

}
