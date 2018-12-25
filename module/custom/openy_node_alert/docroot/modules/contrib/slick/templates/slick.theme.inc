<?php

/**
 * @file
 * Hooks and preprocess functions for the Slick module.
 */

use Drupal\Core\Template\Attribute;
use Drupal\Component\Serialization\Json;
use Drupal\slick\Entity\Slick;

/**
 * Prepares variables for slick.html.twig templates.
 */
function template_preprocess_slick(&$variables) {
  $element   = $variables['element'];
  $defaults  = Slick::htmlSettings();
  $settings  = isset($element['#settings']) ? array_merge($defaults, $element['#settings']) : $defaults;
  $customs   = isset($element['#options']) ? $element['#options'] : [];
  $optionset = isset($element['#optionset']) ? $element['#optionset'] : Slick::load($settings['optionset']);
  $js        = $customs ? array_merge($optionset->getSettings(), $customs) : $optionset->getSettings();

  // Prepare attributes.
  $attributes = &$variables['attributes'];
  $content_attributes = new Attribute();
  $display = $variables['display'] = $settings['display'];

  $id = empty($settings['id']) ? Slick::getHtmlId('slick') : $settings['id'];
  $attributes['id'] = $display == 'thumbnail' ? $id . '-thumbnail' : $id;

  // Sniffs for Views to allow block__no_wrapper, views__no_wrapper, etc.
  // @todo: Use $attributes if TWIG class ordering puts $attributes to the last.
  if ($settings['view_name'] && $settings['current_view_mode']) {
    $settings['attributes']['class'][] = str_replace('_', '-', 'slick--view--' . $settings['view_name']);
    $settings['attributes']['class'][] = str_replace('_', '-', 'slick--view--' . $settings['view_name'] . '--' . $settings['current_view_mode']);
  }

  // @see SlickManager::buildGrid(), and this should make sense.
  $settings['count']   = isset($settings['count']) ? $settings['count'] : count($element['#items']);
  $settings['unslick'] = $settings['unslick'] || $settings['count'] == 1;

  foreach (Slick::jsSettings() as $key => $default) {
    $settings[$key] = isset($js[$key]) ? $js[$key] : $default;
  }

  // Blazy can still lazyload an unslick.
  $settings['lazy'] = empty($settings['lazy']) ? $js['lazyLoad'] : $settings['lazy'];
  if ($display != 'thumbnail') {
    if ($settings['lazy'] == 'blazy' || !empty($settings['blazy'])) {
      if ($settings['lazy'] == 'blazy') {
        $js['lazyLoad'] = 'blazy';
      }
      $attributes['data-blazy'] = empty($settings['blazy_data']) ? '' : Json::encode($settings['blazy_data']);
    }

    if (!empty($settings['media_switch']) && empty($settings['grid'])) {
      $switch = str_replace('_', '-', $settings['media_switch']);
      $attributes['data-' . $switch . '-gallery'] = TRUE;
    }
  }

  if (!empty($js['rtl'])) {
    $attributes['dir'] = 'rtl';
  }

  // Prevents broken slick when only one item given, or an enforced unslick.
  if (!$settings['unslick']) {
    $content_attributes->setAttribute('id', $attributes['id'] . '-slider');

    $variables['arrow_attributes'] = new Attribute();
    if ($display == 'main' && !empty($js['downArrow']) && !empty($js['downArrowTarget'])) {
      $variables['arrow_down_attributes'] = new Attribute();
    }

    // focusOnSelect won't work with empty slide value, so add proper selector.
    // Respects core Grid markups which may wrap .slick__slide within anon DIV.
    if (empty($js['slide']) && !empty($js['focusOnSelect'])) {
      $js['slide'] = ($js['rows'] == 1 && $js['slidesPerRow'] == 1) ? '.slick__slide' : $js['slide'];
    }

    // Add the configuration as JSON object into the slick container.
    if ($json = $optionset->removeDefaultValues($js)) {
      $content_attributes->setAttribute('data-slick', Json::encode($json));
    }
  }

  // Pass settings and attributes to twig.
  $variables['settings'] = $settings;
  $variables['content_attributes'] = $content_attributes;

  // Process individual item.
  $variables['items'] = [];
  foreach ($element['#items'] as $delta => $item) {
    $settings['current_item'] = $display;
    $settings = isset($item['settings']) ? array_merge($settings, $item['settings']) : $settings;
    $item_attributes = isset($item['attributes']) ? $item['attributes'] : [];
    unset($item['settings'], $item['attributes']);

    $theme = $settings['vanilla'] ? 'vanilla' : ($display == 'thumbnail' ? 'thumbnail' : 'slide');

    $slide = [
      '#theme'      => 'slick_' . $theme,
      '#item'       => $item,
      '#delta'      => $delta,
      '#settings'   => $settings,
      '#attributes' => $item_attributes,
    ];
    $variables['items'][$delta] = $slide;
    unset($slide);
  }
}

/**
 * Prepares variables for slick-wrapper.html.twig templates.
 */
function template_preprocess_slick_wrapper(&$variables) {
  foreach (['attributes', 'items', 'settings'] as $key) {
    $variables[$key] = isset($variables['element']["#$key"]) ? $variables['element']["#$key"] : [];
  }
}

/**
 * Prepares common variables for slick item templates.
 */
function _slick_preprocess_slick_item(&$variables) {
  foreach (['attributes', 'delta', 'item', 'settings'] as $key) {
    $default = $key == 'delta' ? NULL : [];
    $variables[$key] = isset($variables['element']["#$key"]) ? $variables['element']["#$key"] : $default;
  }
}

/**
 * Prepares variables for slick-vanilla.html.twig templates.
 */
function template_preprocess_slick_vanilla(&$variables) {
  _slick_preprocess_slick_item($variables);
}

/**
 * Prepares variables for slick-thumbnail.html.twig templates.
 */
function template_preprocess_slick_thumbnail(&$variables) {
  _slick_preprocess_slick_item($variables);
}

/**
 * Prepares variables for slick-slide.html.twig templates.
 */
function template_preprocess_slick_slide(&$variables) {
  _slick_preprocess_slick_item($variables);

  // All slide types -- main, thumbnail, grid, overlay -- may have captions.
  foreach (['alt', 'data', 'link', 'overlay', 'title'] as $key) {
    $variables['item']['caption'][$key] = isset($variables['item']['caption'][$key]) ? $variables['item']['caption'][$key] : [];
  }

  $item     = &$variables['item'];
  $settings = &$variables['settings'];

  // split: Split image from captions if we do have captions, and main image.
  // fullwidth: If full skins, add wrappers to hold caption and overlay.
  // detroy: Remove .slide__content if it is an enforced unslick grid.
  // wrapper: Don't add divities for a single item to have clean markups.
  $item['slide']         = isset($item['slide']) ? $item['slide'] : [];
  $item['caption']       = array_filter($item['caption']);
  $settings['split']     = !empty($item) && !empty($settings['caption']);
  $settings['data']      = !empty($item['caption']['alt']) || !empty($item['caption']['title']) || !empty($item['caption']['data']);
  $settings['fullwidth'] = !empty($settings['skin']) && strpos($settings['skin'], 'full') !== FALSE;
  $settings['grid']      = empty($settings['grid']) ? FALSE : $settings['grid'];
  $settings['detroy']    = $settings['current_item'] == 'main' && !empty($settings['grid']) && !empty($settings['unslick']);
  $settings['wrapper']   = $settings['count'] > 1 && $settings['current_item'] != 'grid';
}

/**
 * Prepares variables for slick-grid.html.twig templates.
 */
function template_preprocess_slick_grid(&$variables) {
  $variables['settings'] = $variables['element']['#settings'];
  $variables['delta']    = isset($variables['element']['#delta']) ? $variables['element']['#delta'] : 0;
  $variables['grid_id']  = 'grid';

  $settings = $variables['settings'];

  // By default Slick only supports Grid Foundation, adds relevant grid_id for
  // optional Style: CSS3 Columns, and probably future flexbox.
  if (!empty($settings['style']) && $settings['style'] != 'slick') {
    $variables['grid_id'] = $settings['style'];
  }

  if (!empty($settings['media_switch'])) {
    $switch = str_replace('_', '-', $settings['media_switch']);
    $variables['attributes']['data-' . $switch . '-gallery'] = TRUE;
  }

  $variables['items'] = [];
  foreach ($variables['element']['#items'] as $delta => $item) {
    $settings = isset($item['settings']) ? array_merge($settings, $item['settings']) : $settings;
    $settings['current_item'] = 'grid';
    unset($item['settings']);

    $item_attributes = [];
    if (!empty($item['#attributes'])) {
      $item_attributes = $item['#attributes'];
    }

    $slide['content'] = [
      '#theme'    => empty($settings['vanilla']) ? 'slick_slide' : 'slick_vanilla',
      '#item'     => $item,
      '#delta'    => $delta,
      '#settings' => $settings,
    ];

    $slide['attributes'] = new Attribute($item_attributes);
    $variables['items'][$delta] = $slide;
    unset($slide);
  }
}

/**
 * Prepares variables for slick-image.html.twig template.
 *
 * @deprecated: Just use blazy.html.twig to reduce function calls.
 */
function template_preprocess_slick_image(&$variables) {
  template_preprocess_blazy($variables);
}
