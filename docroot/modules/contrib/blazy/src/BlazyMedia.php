<?php

namespace Drupal\blazy;

use Drupal\image\Entity\ImageStyle;

/**
 * Provides extra media utilities without dependencies on Media Entity, etc.
 */
class BlazyMedia {

  /**
   * Builds the media field which cannot be displayed using theme_blazy().
   *
   * Some use URLs from inputs, some local files.
   *
   * @param object $media
   *   The media being rendered.
   *
   * @return array|bool
   *   The renderable array of the media field, or false if not applicable.
   */
  public static function build($media, $settings = []) {
    // Prevents fatal error with disconnected internet when having ME Facebook,
    // ME SlideShare, resorted to static thumbnails to avoid broken displays.
    if (!empty($settings['input_url'])) {
      // @todo: Remove when ME Facebook alike handles this.
      try {
        $response = \Drupal::httpClient()->get($settings['input_url']);
      }
      catch (\Exception $e) {
        return FALSE;
      }
    }

    $build = $media->get($settings['source_field'])->view($settings['view_mode']);
    $build['#settings'] = $settings;

    return self::wrap($build);
  }

  /**
   * Returns a field to be wrapped by theme_container().
   *
   * Currently Instagram, and SlideShare are known to use iframe, and thus can
   * be converted into a responsive Blazy with fluid ratio. The rest are
   * returned as is, only wrapped by .media wrapper for consistency with complex
   * interaction like EB.
   *
   * @param array $field
   *   The source renderable array $field.
   *
   * @return array
   *   The new renderable array of the media item wrapped by theme_container().
   */
  public static function wrap(array $field = []) {
    // Media entity is a single being, reasonable to work with multi-value?
    $item       = $field[0];
    $settings   = isset($field['#settings']) ? $field['#settings'] : [];
    $attributes = &$item['#attributes'];
    $iframe     = isset($item['#tag']) && $item['#tag'] == 'iframe';

    // Converts iframes into lazyloaded ones.
    if ($iframe && !empty($attributes['src'])) {
      $attributes['data-src'] = $attributes['src'];
      $attributes['class'][] = 'b-lazy media__iframe media__element';
      $attributes['src'] = 'about:blank';
      $attributes['allowfullscreen'] = TRUE;
    }

    // Wraps the media item to allow consistency for EB/SB.
    $build = [
      '#theme'      => 'container',
      '#children'   => $item,
      '#attributes' => ['class' => ['media']],
      '#settings'   => $settings,
    ];

    if (!empty($settings['bundle'])) {
      $build['#attributes']['class'][] = 'media--' . str_replace('_', '-', $settings['bundle']);
    }

    // Adds helper for Entity Browser small thumbnail selection.
    if (!empty($settings['thumbnail_style']) && !empty($settings['uri'])) {
      $build['#attributes']['data-thumb'] = ImageStyle::load($settings['thumbnail_style'])->buildUrl($settings['uri']);
    }

    // Currently known media entities using iframe: Instagram.
    if ($iframe) {
      $build['#attributes']['class'][] = 'media--ratio';

      if (!empty($attributes['width']) && !empty($attributes['height'])) {
        $padding_bottom = round((($attributes['height'] / $attributes['width']) * 100), 2);
        $build['#attributes']['style'] = 'padding-bottom: ' . $padding_bottom . '%';
      }
    }
    else {
      $build['#attributes']['class'][] = 'media--rendered';
    }

    // Clone relevant keys as field wrapper is no longer in use.
    foreach (['attached', 'cache'] as $key) {
      if (isset($field["#$key"])) {
        $build["#$key"] = $field["#$key"];
      }
    }

    return $build;
  }

}
