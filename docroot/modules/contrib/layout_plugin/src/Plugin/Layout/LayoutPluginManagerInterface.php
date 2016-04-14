<?php
/**
 * @file
 * Contains \Drupal\layout_plugin\Plugin\Layout\LayoutPluginManagerInterface.
 */

namespace Drupal\layout_plugin\Plugin\Layout;

use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Provides an interface for the discovery and instantiation of layout plugins.
 */
interface LayoutPluginManagerInterface extends PluginManagerInterface {

  /**
   * Get all available layouts as an options array.
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
   */
  public function getLayoutOptions(array $params = []);

  /**
   * Get theme implementations for layouts that give only a template.
   *
   * @return array
   *   An associative array of the same format as returned by hook_theme().
   *
   * @see hook_theme()
   */
  public function getThemeImplementations();

  /**
   * Modifies the theme implementations for the layouts that we registered.
   *
   * @param array &$theme_registry
   *   An associative array of the same format as passed to hook_theme_registry_alter().
   *
   * @see hook_theme_registry_alter()
   */
  public function alterThemeImplementations(array &$theme_registry);

  /**
   * Get library info for layouts that want to automatically register CSS.
   *
   * @return array
   *   An associative array of the same format as returned by
   *   hook_library_info_build().
   *
   * @see hook_library_info_build()
   */
  public function getLibraryInfo();

}
