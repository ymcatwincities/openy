<?php

namespace Drupal\plugin\Plugin\Field\FieldWidget;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\plugin\Plugin\Plugin\PluginSelector\PluginSelectorManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Derives plugin selector field widgets.
 */
class PluginSelectorDeriver extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The plugin selector manager.
   *
   * @var \Drupal\plugin\Plugin\Plugin\PluginSelector\PluginSelectorManagerInterface
   */
  protected $pluginSelectorManager;

  /**
   * Constructs a new class instance.
   */
  public function __construct(PluginSelectorManagerInterface $plugin_selector_manager) {
    $this->pluginSelectorManager = $plugin_selector_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static($container->get('plugin.manager.plugin.plugin_selector'));
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    foreach ($this->pluginSelectorManager->getDefinitions() as $plugin_id => $plugin_definition) {
      $this->derivatives[$plugin_id] = array(
          'description' => isset($plugin_definition['description']) ? $plugin_definition['description'] : NULL,
          'label' => $plugin_definition['label'],
          'plugin_selector_id' => $plugin_id,
        ) + $base_plugin_definition;
    }

    return parent::getDerivativeDefinitions($base_plugin_definition);
  }
}
