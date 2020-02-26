<?php

namespace Drupal\paragraph_skins;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\Discovery\ContainerDerivativeDiscoveryDecorator;
use Drupal\Core\Plugin\Discovery\YamlDiscovery;


/**
 * Manages discovery and instantiation of skin plugins.
 *
 * @see \Drupal\state_machine\Plugin\WorkflowGroup\WorkflowGroupInterface
 * @see plugin_api
 */
class SkinManager extends DefaultPluginManager implements SkinManagerInterface {

  /**
   * Default values for each workflow_group plugin.
   *
   * @var array
   */
  protected $defaults = [
    'paragraph_type' => '',
    'label' => '',
    'theme' => '',
    'library' => '',
    'group' => '',
  ];

  /**
   * Constructs a new ParagraphSkins object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   */
  public function __construct(ModuleHandlerInterface $module_handler, CacheBackendInterface $cache_backend) {
    $this->moduleHandler = $module_handler;
    $this->setCacheBackend($cache_backend, 'paragraph_skins', ['paragraph_skins']);
    $this->alterInfo('paragraph_skins');
  }

  /**
   * {@inheritdoc}
   */
  protected function getDiscovery() {
    if (!isset($this->discovery)) {
      $this->discovery = new YamlDiscovery('paragraph_skins', $this->moduleHandler->getModuleDirectories());
      $this->discovery = new ContainerDerivativeDiscoveryDecorator($this->discovery);
    }
    return $this->discovery;
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitionsByParagraphType($type_id = '') {
    $definitions = $this->getDefinitions();
    if ($type_id) {
      $definitions = array_filter($definitions, function ($definition) use ($type_id) {
        return $definition['paragraph_type'] == $type_id;
      });
    }

    return $definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitionsByThemeAndParagraphKey($type_id = '', $theme_key = '') {
    $definitions = $this->getDefinitions();
    if ($theme_key) {
      $definitions = array_filter($definitions, function ($definition) use ($theme_key) {
        return $definition['theme'] == $theme_key;
      });
    }

    if ($type_id) {
      $definitions = array_filter($definitions, function ($definition) use ($type_id) {
        return $definition['paragraph_type'] == $type_id;
      });
    }

    return $definitions;
  }

}
