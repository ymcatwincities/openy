<?php

namespace Drupal\slick;

use Drupal\slick\Entity\Slick;
use Drupal\blazy\BlazyFormatterManager;

/**
 * Implements SlickFormatterInterface.
 */
class SlickFormatter extends BlazyFormatterManager implements SlickFormatterInterface {

  /**
   * {@inheritdoc}
   */
  public function buildSettings(array &$build, $items) {
    $settings = &$build['settings'];

    // Prepare integration with Blazy.
    $settings['item_id']   = 'slide';
    $settings['namespace'] = 'slick';

    // Pass basic info to parent::buildSettings().
    parent::buildSettings($build, $items);

    // Slick specific stuffs.
    $build['optionset'] = Slick::load($settings['optionset']);

    // Ensures deleted optionset while being used doesn't screw up.
    if (empty($build['optionset'])) {
      $build['optionset'] = Slick::load('default');
    }

    if (!isset($settings['nav'])) {
      $settings['nav'] = !empty($settings['optionset_thumbnail']) && isset($items[1]);
    }

    // Do not bother for SlickTextFormatter, or when vanilla is on.
    if (empty($settings['vanilla'])) {
      $lazy              = $build['optionset']->getSetting('lazyLoad');
      $settings['blazy'] = $lazy == 'blazy' || !empty($settings['blazy']);
      $settings['lazy']  = $settings['blazy'] ? 'blazy' : $lazy;

      if (empty($settings['blazy'])) {
        $settings['lazy_class'] = $settings['lazy_attribute'] = 'lazy';
      }
    }
    else {
      // Nothing to work with Vanilla on, disable the asnavfor, else JS error.
      $settings['nav'] = FALSE;
    }

    $settings['overridables'] = array_filter($settings['overridables']);
  }

  /**
   * Gets the thumbnail image.
   */
  public function getThumbnail($settings = []) {
    $thumbnail = [];
    if (!empty($settings['uri'])) {
      $thumbnail = [
        '#theme'      => 'image_style',
        '#style_name' => isset($settings['thumbnail_style']) ? $settings['thumbnail_style'] : 'thumbnail',
        '#uri'        => $settings['uri'],
      ];

      foreach (['height', 'width', 'alt', 'title'] as $data) {
        $thumbnail["#$data"] = isset($settings[$data]) ? $settings[$data] : NULL;
      }
    }
    return $thumbnail;
  }

}
