<?php

namespace Drupal\slick;

/**
 * Defines re-usable services and functions for slick plugins.
 */
interface SlickManagerInterface {

  /**
   * Returns a cacheable renderable array of a single slick instance.
   *
   * @param array $build
   *   An associative array containing:
   *   - items: An array of slick contents: text, image or media.
   *   - options: An array of key:value pairs of custom JS overrides.
   *   - optionset: The cached optionset object to avoid multiple invocations.
   *   - settings: An array of key:value pairs of HTML/layout related settings.
   *
   * @return array
   *   The cacheable renderable array of a slick instance, or empty array.
   */
  public static function slick(array $build = []);

  /**
   * Returns a renderable array of both main and thumbnail slick instances.
   *
   * @param array $build
   *   An associative array containing:
   *   - items: An array of slick contents: text, image or media.
   *   - options: An array of key:value pairs of custom JS overrides.
   *   - optionset: The cached optionset object to avoid multiple invocations.
   *   - settings: An array of key:value pairs of HTML/layout related settings.
   *   - thumb: An associative array of slick thumbnail following the same
   *     structure as the main display: $build['thumb']['items'], etc.
   *
   * @return array
   *   The renderable array of both main and thumbnail slick instances.
   */
  public function build(array $build = []);

}
