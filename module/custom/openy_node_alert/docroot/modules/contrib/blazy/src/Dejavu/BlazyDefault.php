<?php

namespace Drupal\blazy\Dejavu;

/**
 * Defines shared plugin default settings for field formatter and Views style.
 *
 * @todo: Consider moving this into Drupal\blazy namespace.
 */
class BlazyDefault {

  /**
   * The supported $breakpoints.
   *
   * @var array
   */
  private static $breakpoints = ['xs', 'sm', 'md', 'lg', 'xl'];

  /**
   * Returns Blazy specific breakpoints.
   */
  public static function getConstantBreakpoints() {
    return self::$breakpoints;
  }

  /**
   * Returns basic plugin settings.
   */
  public static function baseSettings() {
    return [
      'cache'             => 0,
      'current_view_mode' => '',
      'optionset'         => 'default',
      'skin'              => '',
      'style'             => '',
    ];
  }

  /**
   * Returns image-related field formatter and Views settings.
   */
  public static function baseImageSettings() {
    return [
      'background'             => FALSE,
      'box_caption'            => '',
      'box_caption_custom'     => '',
      'box_style'              => '',
      'box_media_style'        => '',
      'breakpoints'            => [],
      'caption'                => [],
      'image_style'            => '',
      'media_switch'           => '',
      'ratio'                  => '',
      'responsive_image_style' => '',
      'sizes'                  => '',
    ];
  }

  /**
   * Returns image-related field formatter and Views settings.
   */
  public static function imageSettings() {
    return [
      'iframe_lazy'     => TRUE,
      'icon'            => '',
      'layout'          => '',
      'thumbnail_style' => '',
      'view_mode'       => '',
    ] + self::baseSettings() + self::baseImageSettings();
  }

  /**
   * Returns Views specific settings.
   */
  public static function viewsSettings() {
    return [
      'class'   => '',
      'id'      => '',
      'image'   => '',
      'link'    => '',
      'overlay' => '',
      'title'   => '',
      'vanilla' => FALSE,
    ];
  }

  /**
   * Returns fieldable entity formatter and Views settings.
   */
  public static function extendedSettings() {
    return self::viewsSettings() + self::imageSettings();
  }

  /**
   * Returns optional grid field formatter and Views settings.
   */
  public static function gridSettings() {
    return [
      'grid'        => 0,
      'grid_header' => '',
      'grid_medium' => 0,
      'grid_small'  => 0,
      'style'       => '',
    ];
  }

  /**
   * Returns sensible default options common for entities lacking of UI.
   */
  public static function entitySettings() {
    return [
      'blazy'        => TRUE,
      'iframe_lazy'  => TRUE,
      'lazy'         => 'blazy',
      'media_switch' => 'media',
      'ratio'        => 'fluid',
      'rendered'     => FALSE,
      'view_mode'    => 'default',
      '_detached'    => TRUE,
    ];
  }

}
