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
  protected $defaults = [];

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
   * Returns list of skins for paragraph.
   *
   * @param string $type_id Paragraph type id
   *
   * @return array
   */
  public function getSkinsForParagraph($type_id) {
    $definitions = $this->getDefinitions();

    $skins = [
      'none' => t('None'),
    ];

    foreach ($definitions as $module_skins) {
      unset($module_skins['provider'], $module_skins['id']);
      foreach ($module_skins as $skin_definition) {
        if (!isset($skin_definition['paragraph_type']) || $skin_definition['paragraph_type'] != $type_id) {
          continue;
        }

        $skins[$skin_definition['name']] = $skin_definition['label'];
      }
    }

    return $skins;
  }

  /**
   * Returns list of libraries.
   *
   * @return array
   */
  public function getLibraries($type_id, $skin_name) {
    $definitions = $this->getDefinitions();
    $base_theme_name = $this->getBaseThemeName();
    $libraries = [];

    foreach ($definitions as $module_skins) {
      unset($module_skins['provider'], $module_skins['id']);
      foreach ($module_skins as $skin_definition) {
        if (!isset($skin_definition['paragraph_type']) || $skin_definition['paragraph_type'] != $type_id) {
          continue;
        }
        if (!isset($skin_definition['name']) || $skin_definition['name'] != $skin_name) {
          continue;
        }
        if (isset($skin_definition['library'])) {
          $libraries[] = $skin_definition['library'];
        }
        if (isset($skin_definition['theme_library'][$base_theme_name])) {
          $libraries[] = $skin_definition['theme_library'][$base_theme_name];
        }
      }
    }

    return $libraries;
  }

  /**
   * Returns current base theme name.
   *
   * @return string
   */
  public function getBaseThemeName() {
    if (!empty($this->base_theme_name)) {
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
