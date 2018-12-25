<?php

namespace Drupal\blazy;

use Drupal\Core\Template\Attribute;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\Component\Serialization\Json;
use Drupal\image\Entity\ImageStyle;
use Drupal\blazy\Dejavu\BlazyDefault;

/**
 * Implements BlazyInterface.
 */
class Blazy implements BlazyInterface {

  /**
   * Defines constant placeholder Data URI image.
   */
  const PLACEHOLDER = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

  /**
   * Prepares variables for blazy.html.twig templates.
   */
  public static function buildAttributes(&$variables) {
    $element = $variables['element'];
    foreach (['captions', 'item_attributes', 'settings', 'url'] as $key) {
      $variables[$key] = isset($element["#$key"]) ? $element["#$key"] : [];
    }

    // Load the supported formatter variables for the possesive blazy wrapper.
    $item             = isset($element['#item']) ? $element['#item'] : [];
    $settings         = &$variables['settings'];
    $attributes       = &$variables['attributes'];
    $image_attributes = &$variables['item_attributes'];

    // Provides sensible defaults to shut up notices when lacking of settings.
    foreach (['icon', 'image_style', 'media_switch', 'player', 'scheme'] as $key) {
      $settings[$key] = isset($settings[$key]) ? $settings[$key] : '';
    }

    $settings['type']      = empty($settings['type']) ? 'image' : $settings['type'];
    $settings['ratio']     = empty($settings['ratio']) ? '' : str_replace(':', '', $settings['ratio']);
    $settings['item_id']   = empty($settings['item_id']) ? 'blazy' : $settings['item_id'];
    $settings['namespace'] = empty($settings['namespace']) ? 'blazy' : $settings['namespace'];

    self::buildUrl($settings, $item);

    // Do not proceed if no URI is provided.
    // URI is stored within settings, not theme_blazy() property, as it is
    // always called for different purposes prior to arriving at theme_blazy().
    if (empty($settings['uri'])) {
      return;
    }

    // Supports non-blazy formatter, that is, responsive image theme.
    $image = &$variables['image'];
    $media = !empty($settings['embed_url']) && in_array($settings['type'], ['audio', 'video']);

    // The regular non-responsive, non-lazyloaded image URI where image_url may
    // contain image_style which is not expected by responsive_image.
    $image['#uri'] = empty($settings['image_url']) ? $settings['uri'] : $settings['image_url'];

    // Thumbnails.
    // With CSS background, IMG may be empty, add thumbnail to the container.
    if (!empty($settings['thumbnail_style'])) {
      $attributes['data-thumb'] = ImageStyle::load($settings['thumbnail_style'])->buildUrl($settings['uri']);
    }

    // Check whether we have responsive image, or lazyloaded one.
    if (!empty($settings['responsive_image_style_id'])) {
      $image['#type'] = 'responsive_image';
      $image['#responsive_image_style_id'] = $settings['responsive_image_style_id'];
      $image['#uri'] = $settings['uri'];

      // Disable aspect ratio which is not yet supported due to complexity.
      $settings['ratio'] = FALSE;
    }
    else {
      // Supports non-lazyloaded image.
      $image['#theme'] = 'image';

      // Aspect ratio to fix layout reflow with lazyloaded images responsively.
      // This is outside 'lazy' to allow non-lazyloaded iframes use this too.
      if (!empty($settings['width'])) {
        if (!empty($settings['ratio']) && in_array($settings['ratio'], ['enforced', 'fluid'])) {
          $padding_bottom = empty($settings['padding_bottom']) ? round((($settings['height'] / $settings['width']) * 100), 2) : $settings['padding_bottom'];
          $attributes['style'] = 'padding-bottom: ' . $padding_bottom . '%';
          $settings['_breakpoint_ratio'] = $settings['ratio'];
        }

        // Only output dimensions for non-responsive images.
        $image_attributes['height'] = $settings['height'];
        $image_attributes['width']  = $settings['width'];
      }

      if (!empty($settings['lazy'])) {
        $image['#uri'] = static::PLACEHOLDER;

        // Attach data attributes to either IMG tag, or DIV container.
        if (empty($settings['background']) || empty($settings['blazy'])) {
          self::buildBreakpointAttributes($image_attributes, $settings);
        }

        // Supports both Slick and Blazy CSS background lazyloading.
        if (!empty($settings['background'])) {
          self::buildBreakpointAttributes($attributes, $settings);
          $attributes['class'][] = 'media--background';

          // Blazy doesn't need IMG to lazyload CSS background. Slick does.
          if (!empty($settings['blazy'])) {
            $image = [];
          }
        }

        // Multi-breakpoint aspect ratio only applies if lazyloaded.
        if (!empty($settings['blazy_data']['dimensions'])) {
          $attributes['data-dimensions'] = Json::encode($settings['blazy_data']['dimensions']);
        }
      }
    }

    // Image is optional for Video, and Blazy CSS background images.
    if ($image) {
      $image_attributes['alt'] = isset($item->alt) ? $item->alt : NULL;

      // Do not output an empty 'title' attribute.
      if (isset($item->title) && (Unicode::strlen($item->title) != 0)) {
        $image_attributes['title'] = $item->title;
      }

      $image_attributes['class'][] = 'media__image media__element';
      $image['#attributes'] = $image_attributes;
    }

    // Prepares a media player, and allows a tiny video preview without iframe.
    if ($media && empty($settings['_noiframe'])) {
      self::buildIframeAttributes($variables);
    }

    // Provides optional attributes.
    foreach (['caption', 'media', 'url', 'wrapper'] as $key) {
      $attr = $key . '_attributes';
      $variables[$attr] = empty($element['#' . $attr]) ? [] : new Attribute($element['#' . $attr]);
    }
  }

  /**
   * Modifies variables for iframes.
   */
  public static function buildIframeAttributes(&$variables) {
    // Prepares a media player, and allows a tiny video preview without iframe.
    // image : If iframe switch disabled, fallback to iframe, remove image.
    // player: If no colorbox/photobox, it is an image to iframe switcher.
    // data- : Gets consistent with colorbox to share JS manipulation.
    $settings           = &$variables['settings'];
    $variables['image'] = empty($settings['media_switch']) ? [] : $variables['image'];
    $settings['player'] = empty($settings['lightbox']) && $settings['media_switch'] != 'content';
    $iframe['data-src'] = $settings['embed_url'];
    $iframe['src']      = empty($settings['iframe_lazy']) ? $settings['embed_url'] : 'about:blank';

    // Only lazyload if media switcher is empty, but iframe lazy enabled.
    if (!empty($settings['iframe_lazy']) && empty($settings['media_switch'])) {
      $iframe['class'][] = 'b-lazy';
    }

    // Prevents broken iframe when aspect ratio is empty.
    if (empty($settings['ratio']) && !empty($settings['width'])) {
      $iframe['width']  = $settings['width'];
      $iframe['height'] = $settings['height'];
    }

    // Pass iframe attributes to template.
    $settings['autoplay_url'] = empty($settings['autoplay_url']) ? $settings['embed_url'] : $settings['autoplay_url'];
    $variables['iframe_attributes'] = new Attribute($iframe);

    // Iframe is removed on lazyloaded, puts data at non-removable storage.
    $variables['attributes']['data-media'] = Json::encode(['type' => $settings['type'], 'scheme' => $settings['scheme']]);
  }

  /**
   * Provides re-usable breakpoint data-attributes.
   *
   * $settings['breakpoints'] must contain: xs, sm, md, lg breakpoints with
   * the expected keys: width, image_style.
   *
   * @see self::buildAttributes()
   */
  public static function buildBreakpointAttributes(array &$attributes = [], array &$settings = []) {
    $lazy_attribute = empty($settings['lazy_attribute']) ? 'src' : $settings['lazy_attribute'];

    // Defines attributes, builtin, or supported lazyload such as Slick.
    $attributes['class'][] = empty($settings['lazy_class']) ? 'b-lazy' : $settings['lazy_class'];
    $attributes['data-' . $lazy_attribute] = $settings['image_url'];

    // Only provide multi-serving image URLs if breakpoints are provided.
    if (empty($settings['breakpoints'])) {
      return;
    }

    $srcset = $json = [];
    foreach ($settings['breakpoints'] as $key => $breakpoint) {
      if (empty($breakpoint['image_style']) || empty($breakpoint['width'])) {
        continue;
      }

      if ($style = ImageStyle::load($breakpoint['image_style'])) {
        $url = $style->buildUrl($settings['uri']);

        // Supports multi-breakpoint aspect ratio with irregular sizes.
        // Yet, only provide individual dimensions if not already set.
        // @see Drupal\blazy\BlazyManager::setDimensionsOnce().
        if (!empty($settings['_breakpoint_ratio']) && empty($settings['blazy_data']['dimensions'])) {
          $dimensions = [
            'width'  => $settings['width'],
            'height' => $settings['height'],
          ];

          $style->transformDimensions($dimensions, $settings['uri']);
          if ($width = self::widthFromDescriptors($breakpoint['width'])) {
            $json[$width] = round((($dimensions['height'] / $dimensions['width']) * 100), 2);
          }
        }

        $settings['breakpoints'][$key]['url'] = $url;

        // @todo: Recheck library if multi-styled BG is still supported anyway.
        // Confirmed: still working with GridStack multi-image-style per item.
        if (!empty($settings['background'])) {
          $attributes['data-src-' . $key] = $url;
        }
        elseif (!empty($breakpoint['width'])) {
          $width = trim($breakpoint['width']);
          $width = is_numeric($width) ? $width . 'w' : $width;
          $srcset[] = $url . ' ' . $width;
        }
      }
    }

    if ($srcset) {
      $settings['srcset'] = implode(', ', $srcset);

      $attributes['srcset'] = '';
      $attributes['data-srcset'] = $settings['srcset'];
      $attributes['sizes'] = '100w';

      if (!empty($settings['sizes'])) {
        $attributes['sizes'] = trim($settings['sizes']);
        unset($attributes['height'], $attributes['width']);
      }
    }

    if ($json) {
      $settings['blazy_data']['dimensions'] = $json;
    }
  }

  /**
   * Builds URLs, cache tags, and dimensions for individual image.
   */
  public static function buildUrl(array &$settings = [], $item = NULL) {
    // Blazy already sets URI, yet set fallback for direct theme_blazy() call.
    if (empty($settings['uri']) && $item) {
      $settings['uri'] = ($entity = $item->entity) && empty($item->uri) ? $entity->getFileUri() : $item->uri;
    }

    if (empty($settings['uri'])) {
      return;
    }

    // Lazyloaded elements expect image URL, not URI.
    if (empty($settings['image_url'])) {
      $settings['image_url'] = file_create_url($settings['uri']);
    }

    // Sets dimensions.
    // VEF without image style, or image style with crop, may already set these.
    if (empty($settings['width'])) {
      $settings['width']  = isset($item->width) ? $item->width : NULL;
      $settings['height'] = isset($item->height) ? $item->height : NULL;
    }

    // Image style modifier can be multi-style images such as GridStack.
    if (!empty($settings['image_style']) && ($style = ImageStyle::load($settings['image_style']))) {
      // Image URLs, as opposed to URIs, are expected by lazyloaded images.
      $settings['image_url']  = $style->buildUrl($settings['uri']);
      $settings['cache_tags'] = $style->getCacheTags();

      // Only re-calculate dimensions if not cropped, nor already set.
      if (empty($settings['_dimensions'])) {
        $dimensions = [
          'width'  => $settings['width'],
          'height' => $settings['height'],
        ];

        $style->transformDimensions($dimensions, $settings['uri']);
        $settings['height'] = $dimensions['height'];
        $settings['width']  = $dimensions['width'];
      }
    }
  }

  /**
   * Checks if an image style contains crop effect.
   */
  public static function isCrop($style = NULL) {
    foreach ($style->getEffects() as $uuid => $effect) {
      if (strpos($effect->getPluginId(), 'crop') !== FALSE) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Gets the numeric "width" part from a descriptor.
   */
  public static function widthFromDescriptors($descriptor = '') {
    // Dynamic multi-serving aspect ratio with backward compatibility.
    $descriptor = trim($descriptor);
    if (is_numeric($descriptor)) {
      return $descriptor;
    }

    // Cleanup w descriptor to fetch numerical width for JS aspect ratio.
    $width = strpos($descriptor, "w") !== FALSE ? str_replace('w', '', $descriptor) : $descriptor;

    // If both w and x descriptors are provided.
    if (strpos($descriptor, " ") !== FALSE) {
      // If the position is expected: 640w 2x.
      list($width, $px) = array_pad(array_map('trim', explode(" ", $width, 2)), 2, NULL);

      // If the position is reversed: 2x 640w.
      if (is_numeric($px) && strpos($width, "x") !== FALSE) {
        $width = $px;
      }
    }

    return $width;
  }

  /**
   * Implements hook_config_schema_info_alter().
   */
  public static function configSchemaInfoAlter(array &$definitions, $formatter = 'blazy_base', $settings = []) {
    if (isset($definitions[$formatter])) {
      $mappings = &$definitions[$formatter]['mapping'];
      $settings = $settings ?: BlazyDefault::extendedSettings() + BlazyDefault::gridSettings();
      foreach ($settings as $key => $value) {
        // Seems double is ignored, and causes a missing schema, unlike float.
        $type = gettype($value);
        $type = $type == 'double' ? 'float' : $type;
        $mappings[$key]['type'] = $key == 'breakpoints' ? 'mapping' : (is_array($value) ? 'sequence' : $type);

        if (!is_array($value)) {
          $mappings[$key]['label'] = Unicode::ucfirst(str_replace('_', ' ', $key));
        }
      }

      if (isset($mappings['breakpoints'])) {
        foreach (BlazyDefault::getConstantBreakpoints() as $breakpoint) {
          $mappings['breakpoints']['mapping'][$breakpoint]['type'] = 'mapping';
          foreach (['breakpoint', 'width', 'image_style'] as $item) {
            $mappings['breakpoints']['mapping'][$breakpoint]['mapping'][$item]['type']  = 'string';
            $mappings['breakpoints']['mapping'][$breakpoint]['mapping'][$item]['label'] = Unicode::ucfirst(str_replace('_', ' ', $item));
          }
        }
      }

      // @todo: Drop non-UI stuffs.
      foreach (['dimension', 'display', 'item_id'] as $key) {
        $mappings[$key]['type'] = 'string';
      }
    }
  }

  /**
   * Return blazy global config.
   */
  public static function getConfig($setting_name = '', $settings = 'blazy.settings') {
    $config = \Drupal::service('config.factory')->get($settings);
    return empty($setting_name) ? $config->get() : $config->get($setting_name);
  }

  /**
   * Returns the HTML ID of a single instance.
   */
  public static function getHtmlId($string = 'blazy', $id = '') {
    $blazy_id = &drupal_static('blazy_id', 0);

    // Do not use dynamic Html::getUniqueId, otherwise broken AJAX.
    return empty($id) ? Html::getId($string . '-' . ++$blazy_id) : $id;
  }

}
