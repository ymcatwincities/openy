<?php

namespace Drupal\layout_plugin;

/**
 * Class Layout.
 */
class Layout {

  /**
   * Returns the plugin manager for the Layout plugin type.
   *
   * @return \Drupal\layout_plugin\Plugin\Layout\LayoutPluginManagerInterface
   *   Layout manager.
   */
  public static function layoutPluginManager() {
    return \Drupal::service('plugin.manager.layout_plugin');
  }

  /**
   * Return all available layout as an options array.
   *
   * If group_by_category option/parameter passed group the options by
   * category.
   *
   * @param array $params
   *   (optional) An associative array with the following keys:
   *   - group_by_category: (bool) If set to TRUE, return an array of arrays
   *   grouped by the category name; otherwise, return a single-level
   *   associative array.
   *
   * @return array
   *   Layout options, as array.
   *
   * @deprecated
   *   Use \Drupal\layout_plugin\Plugin\Layout\LayoutPluginManagerInterface::getLayoutOptions().
   */
  public static function getLayoutOptions(array $params = []) {
    return static::layoutPluginManager()->getLayoutOptions($params);
  }

  /**
   * Return theme implementations for layouts that give only a template.
   *
   * @return array
   *   An associative array of the same format as returned by hook_theme().
   *
   * @see hook_theme()
   *
   * @deprecated
   *   Use \Drupal\layout_plugin\Plugin\Layout\LayoutPluginManagerInterface::getThemeImplementations().
   */
  public static function getThemeImplementations() {
    return static::layoutPluginManager()->getThemeImplementations();
  }

  /**
   * Modifies the theme implementations for the layouts that we registered.
   *
   * @param array &$theme_registry
   *   An associative array of the same format as passed to hook_theme_registry_alter().
   *
   * @see hook_theme_registry_alter()
   *
   * @deprecated
   *   Use \Drupal\layout_plugin\Plugin\Layout\LayoutPluginManagerInterface::alterThemeImplementations().
   */
  public static function alterThemeImplementations(array &$theme_registry) {
    static::layoutPluginManager()->alterThemeImplementations($theme_registry);
  }

  /**
   * Return library info for layouts that want to automatically register CSS.
   *
   * @return array
   *   An associative array of the same format as returned by
   *   hook_library_info_build().
   *
   * @see hook_library_info_build()
   *
   * @deprecated
   *   Use \Drupal\layout_plugin\Plugin\Layout\LayoutPluginManagerInterface::alterThemeImplementations().
   */
  public static function getLibraryInfo() {
    return static::layoutPluginManager()->getLibraryInfo();
  }

}
