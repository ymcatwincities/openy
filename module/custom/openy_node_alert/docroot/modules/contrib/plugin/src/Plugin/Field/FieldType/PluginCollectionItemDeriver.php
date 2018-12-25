<?php

namespace Drupal\plugin\Plugin\Field\FieldType;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\plugin\PluginType\PluginTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Derives plugin collection field items.
 */
class PluginCollectionItemDeriver extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The plugin type manager.
   *
   * @var \Drupal\plugin\PluginType\PluginTypeManagerInterface
   */
  protected $pluginTypeManager;

  /**
   * Constructs a new class instance.
   *
   * @param \Drupal\plugin\PluginTypeManagerInterface
   *   The plugin type manager.
   */
  public function __construct(PluginTypeManagerInterface $plugin_type_manager) {
    $this->pluginTypeManager = $plugin_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static($container->get('plugin.plugin_type_manager'));
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    foreach ($this->pluginTypeManager->getPluginTypes() as $plugin_type) {
      if ($plugin_type->isFieldType()) {
        $this->derivatives[$plugin_type->getId()] = array(
            'description' => $plugin_type->getDescription(),
            'label' => $plugin_type->getLabel(),
            'plugin_type_id' => $plugin_type->getId(),
          ) + $base_plugin_definition;
      }
    }

    return parent::getDerivativeDefinitions($base_plugin_definition);
  }
}
