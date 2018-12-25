<?php

namespace Drupal\plugin\Controller;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\plugin\PluginType\PluginTypeInterface;
use Drupal\plugin\PluginType\PluginTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Handles the "list plugin types" route.
 */
class ListPluginTypes extends ListBase {

  /**
   * The plugin type manager.
   *
   * @var \Drupal\plugin\PluginType\PluginTypeManagerInterface
   */
  protected $pluginTypeManager;

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translator.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\plugin\PluginType\PluginTypeManagerInterface
   *   The plugin type manager.
   */
  public function __construct(TranslationInterface $string_translation, ModuleHandlerInterface $module_handler, PluginTypeManagerInterface $plugin_type_manager) {
    parent::__construct($string_translation, $module_handler);
    $this->pluginTypeManager = $plugin_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('string_translation'), $container->get('module_handler'), $container->get('plugin.plugin_type_manager'));
  }

  /**
   * Handles the route.
   *
   * @return mixed[]
   *   A render array.
   */
  public function execute() {
    $build = [
      '#empty' => $this->t('There are no available plugin types.'),
      '#header' => [$this->t('Type'), $this->t('Description'), $this->t('Provider'), $this->t('Operations')],
      '#type' => 'table',
    ];
    $plugin_types = $this->pluginTypeManager->getPluginTypes();
    uasort($plugin_types, function (PluginTypeInterface $plugin_type_a, PluginTypeInterface $plugin_type_b) {
      return strnatcasecmp($plugin_type_a->getLabel(), $plugin_type_b->getLabel());
    });
    foreach ($plugin_types as $plugin_type_id => $plugin_type) {
      $operations_provider = $plugin_type->getOperationsProvider();
      $operations = $operations_provider ? $operations_provider->getOperations($plugin_type_id) : [];

      $build[$plugin_type_id]['label'] = [
        '#markup' => $plugin_type->getLabel(),
      ];
      $build[$plugin_type_id]['description'] = [
        '#markup' => $plugin_type->getDescription(),
      ];
      $build[$plugin_type_id]['provider'] = [
        '#markup' => $this->getProviderLabel($plugin_type->getProvider()),
      ];
      $build[$plugin_type_id]['operations'] = [
        '#links' => $operations,
        '#type' => 'operations',
      ];
    }

    return $build;
  }

}
