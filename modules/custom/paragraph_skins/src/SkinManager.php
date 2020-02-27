<?php

namespace Drupal\paragraph_skins;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\Discovery\ContainerDerivativeDiscoveryDecorator;
use Drupal\Core\Plugin\Discovery\YamlDiscovery;
use Drupal\Core\Theme\ThemeManager;


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
   * @var \Drupal\Core\Theme\ThemeManager
   */
  protected $theme_manager;

  /**
   * @var string
   */
  protected $base_theme_name;

  /**
   * Constructs a new ParagraphSkins object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   * @param Drupal\Core\Theme\ThemeManager $theme_manager
   *   Theme manager.
   */
  public function __construct(ModuleHandlerInterface $module_handler, CacheBackendInterface $cache_backend, ThemeManager $theme_manager) {
    $this->moduleHandler = $module_handler;
    $this->theme_manager = $theme_manager;
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
    $definitions = $definitions['paragraph_skins'];
    if ($type_id) {
      $definitions = array_filter($definitions, function ($definition) use ($type_id) {
        return isset($definition['paragraph_type']) && $definition['paragraph_type'] == $type_id;
      });
    }

    return $definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function getLibraries($type_id, $skin_name) {
    $definitions = $this->getDefinitions();
    $definitions = array_filter($definitions['paragraph_skins'], function ($definition) use ($type_id, $skin_name) {
      return isset($definition['paragraph_type'])
        && $definition['paragraph_type'] == $type_id
        && $definition['name'] == $skin_name;
    });

    $base_theme_name = $this->getBaseTheme();

    $libraries = [];
    foreach ($definitions as $definition) {
      if(isset($definition['library'])) {
        $libraries[] = $definition['library'];
      }
      if(isset($definition['theme_library'][$base_theme_name])) {
        $libraries[] = $definition['theme_library'][$base_theme_name];
      }
    }

    return $libraries;
  }

  public function getBaseTheme() {
    if(!empty($this->base_theme_name)) {
      return $this->base_theme_name;
    }

    $active_theme = $this->theme_manager->getActiveTheme();

    foreach ($active_theme->getBaseThemeExtensions() as $base_theme) {
      $this->base_theme_name = $base_theme->getName();
      return $this->base_theme_name;
    }

    $this->base_theme_name = $active_theme->getName();
    return $this->base_theme_name;
  }
}
