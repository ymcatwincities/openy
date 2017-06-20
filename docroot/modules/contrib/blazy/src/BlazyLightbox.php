<?php

namespace Drupal\blazy;

use Drupal\Component\Serialization\Json;
use Drupal\image\Entity\ImageStyle;

/**
 * Provides lightbox utilities.
 */
class BlazyLightbox {

  /**
   * Gets media switch elements: all lightboxes, not content, nor iframe.
   *
   * @param array $element
   *   The element being modified.
   */
  public static function build(array &$element = []) {
    $item     = $element['#item'];
    $settings = &$element['#settings'];
    $type     = empty($settings['type']) ? 'image' : $settings['type'];
    $uri      = $settings['uri'];
    $switch   = $settings['media_switch'];
    $multiple = !empty($settings['count']) && $settings['count'] > 1;

    // Provide relevant URL if it is a lightbox.
    $url_attributes = [];
    $url_attributes['class'] = ['blazy__' . $switch, 'litebox'];
    $url_attributes['data-' . $switch . '-trigger'] = TRUE;

    // If it is a video/audio, otherwise image to image.
    $settings['box_url']    = file_create_url($uri);
    $settings['icon']       = empty($settings['icon']) ? ['#markup' => '<span class="media__icon media__icon--litebox"></span>'] : $settings['icon'];
    $settings['lightbox']   = $switch;
    $settings['box_width']  = isset($item->width) ? $item->width : NULL;
    $settings['box_height'] = isset($item->height) ? $item->height : NULL;

    $dimensions = ['width' => $settings['box_width'], 'height' => $settings['box_height']];
    if (!empty($settings['box_style'])) {
      $box_style = ImageStyle::load($settings['box_style']);
      $box_style->transformDimensions($dimensions, $uri);
      $settings['box_url'] = $box_style->buildUrl($uri);
    }

    // Allows custom work to override this without image style, such as
    // a combo of image, video, Instagram, Facebook, etc.
    if (empty($settings['_box_width'])) {
      $settings['box_width'] = $dimensions['width'];
      $settings['box_height'] = $dimensions['height'];
    }

    $json = [
      'type'   => $type,
      'width'  => $settings['box_width'],
      'height' => $settings['box_height'],
    ];

    if (!empty($settings['embed_url'])) {
      $json['scheme'] = $settings['scheme'];
      $json['width']  = 640;
      $json['height'] = 360;

      // Force autoplay for media URL on lightboxes, saving another click.
      $url = empty($settings['autoplay_url']) ? $settings['embed_url'] : $settings['autoplay_url'];

      // Provides custom lightbox media dimension, if so configured.
      // @todo: Remove for Lightbox media style.
      if (!empty($settings['dimension'])) {
        list($json['width'], $json['height']) = array_pad(array_map('trim', explode("x", $settings['dimension'], 2)), 2, NULL);
      }

      // This allows PhotoSwipe with videos still swipable.
      if (!empty($settings['box_media_style'])) {
        $box_media_style = ImageStyle::load($settings['box_media_style']);
        $box_media_style->transformDimensions($dimensions, $uri);
        $settings['box_url'] = $box_media_style->buildUrl($uri);

        // Allows custom work to override this without image style.
        if (empty($settings['_box_width'])) {
          $settings['box_width']  = $dimensions['width'];
          $settings['box_height'] = $dimensions['height'];
        }

        $json['width']  = $settings['box_width'];
        $json['height'] = $settings['box_height'];
      }

      if ($switch == 'photobox') {
        $url_attributes['rel'] = 'video';
      }
    }
    else {
      $url = $settings['box_url'];
    }

    if ($switch == 'colorbox' && $multiple) {
      $json['rel'] = empty($settings['id']) ? 'blazy_colorbox' : $settings['id'];
    }

    $url_attributes['data-media'] = Json::encode($json);

    if (!empty($settings['box_caption'])) {
      $element['#captions']['lightbox'] = self::buildCaptions($item, $settings);
    }

    $element['#url'] = $url;
    $element['#url_attributes'] = $url_attributes;
  }

  /**
   * Builds lightbox captions.
   *
   * @param object|mixed $item
   *   The \Drupal\image\Plugin\Field\FieldType\ImageItem item, or array when
   *   dealing with Video Embed Field.
   * @param array $settings
   *   The settings to work with.
   *
   * @return array
   *   The renderable array of caption, or empty array.
   */
  public static function buildCaptions($item, array $settings = []) {
    $title   = empty($item->title) ? '' : $item->title;
    $alt     = empty($item->alt) ? '' : $item->alt;
    $delta   = empty($settings['delta']) ? 0 : $settings['delta'];
    $caption = '';

    switch ($settings['box_caption']) {
      case 'auto':
        $caption = $alt ?: $title;
        break;

      case 'alt':
        $caption = $alt;
        break;

      case 'title':
        $caption = $title;
        break;

      case 'alt_title':
      case 'title_alt':
        $alt     = $alt ? '<p>' . $alt . '</p>' : '';
        $title   = $title ? '<h2>' . $title . '</h2>' : '';
        $caption = $settings['box_caption'] == 'alt_title' ? $alt . $title : $title . $alt;
        break;

      case 'entity_title':
        $caption = ($entity = $item->getEntity()) ? $entity->label() : '';
        break;

      case 'custom':
        $caption = '';
        if (!empty($settings['box_caption_custom']) && ($entity = $item->getEntity())) {
          $options = ['clear' => TRUE];
          $caption = \Drupal::token()->replace($settings['box_caption_custom'], [$entity->getEntityTypeId() => $entity, 'file' => $item], $options);

          // Checks for multi-value text fields, and maps its delta to image.
          if (strpos($caption, ", <p>") !== FALSE) {
            $caption = str_replace(", <p>", '| <p>', $caption);
            $captions = explode("|", $caption);
            $caption = isset($captions[$delta]) ? $captions[$delta] : '';
          }
        }
        break;

      default:
        $caption = '';
    }

    return empty($caption) ? [] : ['#markup' => $caption];
  }

}
