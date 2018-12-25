<?php

namespace Drupal\slick;

use Drupal\blazy\Dejavu\BlazyDefault;

/**
 * Defines shared plugin default settings for field formatter and Views style.
 *
 * @see FormatterBase::defaultSettings()
 * @see StylePluginBase::defineOptions()
 */
class SlickDefault extends BlazyDefault {

  /**
   * {@inheritdoc}
   */
  public static function baseSettings() {
    return [
      'override'     => FALSE,
      'overridables' => [],
      'skin_arrows'  => '',
      'skin_dots'    => '',
    ] + parent::baseSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function imageSettings() {
    return [
      'optionset_thumbnail' => '',
      'preserve_keys'       => FALSE,
      'skin_thumbnail'      => '',
      'thumbnail_caption'   => '',
      'thumbnail_effect'    => '',
      'thumbnail_position'  => '',
      'visible_items'       => 0,
    ] + self::baseSettings() + parent::imageSettings() + parent::gridSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function extendedSettings() {
    return [
      'thumbnail' => '',
    ] + self::imageSettings() + parent::extendedSettings();
  }

}
