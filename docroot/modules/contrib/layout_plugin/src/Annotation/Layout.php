<?php

/**
 * @file
 * Contains Drupal\layout_plugin\Annotation\Layout.
 */

namespace Drupal\layout_plugin\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Layout annotation object.
 *
 * Layouts are used to define a list of regions and then output render arrays
 * in each of the regions, usually using a template.
 *
 * Plugin namespace: Plugin\Layout
 *
 * @see \Drupal\layout_plugin\Plugin\Layout\LayoutInterface
 * @see \Drupal\layout_plugin\Plugin\Layout\LayoutBase
 * @see \Drupal\layout_plugin\Plugin\Layout\LayoutPluginManager
 * @see plugin_api
 *
 * @Annotation
 */
class Layout extends Plugin {
  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The layout type.
   *
   * Available options:
   *  - full: Layout for the whole page.
   *  - page: Layout for the main page response.
   *  - partial: A partial layout that is typically used for sub-regions.
   *
   * @var string
   */
  public $type = 'page';

  /**
   * The human-readable name.
   *
   * @war \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * An optional description for advanced layouts.
   *
   * Sometimes layouts are so complex that the name is insufficient to describe
   * a layout such that a visually impaired administrator could layout a page
   * for a non-visually impaired audience. If specified, it will provide a
   * description that is used for accessibility purposes.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description;

  /**
   * The human-readable category.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $category;

  /**
   * The theme hook used to render this layout.
   *
   * If specified, it's assumed that the module or theme registering this layout
   * will also register the theme hook with hook_theme() itself. This is
   * mutually exclusive with 'template' - you can't specify both.
   *
   * @var string optional
   *
   * @see hook_theme()
   */
  public $theme;

  /**
   * The template file to render this layout (relative to the 'path' given).
   *
   * If specified, then the layout_plugin module will register the template with
   * hook_theme() and the module or theme registering this layout does not need
   * to do it. This is mutually exclusive with 'theme' - you can't specify both.
   *
   * @var string optional
   *
   * @see hook_theme()
   */
  public $template;

  /**
   * Base path (relative to current module) to all resources (like the icon).
   *
   * @var string optional
   */
  public $path;

  /**
   * The asset library.
   *
   * If specified, it's assumed that the module or theme registering this layout
   * will also register the library in its *.libraries.yml itself. This is
   * mutually exclusive with 'css' - you can't specify both.
   *
   * @var string optional
   */
  public $library;

  /**
   * The CSS file.
   *
   * If specified, then the layout_plugin module will register the library for
   * this CSS file automatically and the module or theme registering this layout
   * does not need to do it. This is mutually exclusive with 'library' - you
   * can't specify both.
   *
   * @var string optional
   */
  public $css;

  /**
   * The path to the preview image (relative to the base path).
   *
   * @var string optional
   */
  public $icon;

  /**
   * An associative array of regions in this layout.
   *
   * The key of the array is the machine name of the region, and the value is
   * an associative array with the following keys:
   * - label: (string) The human-readable name of the region.
   *
   * An remaining keys may have special meaning for the given layout plugin, but
   * are undefined here.
   *
   * @var array
   */
  public $regions = array();

}
