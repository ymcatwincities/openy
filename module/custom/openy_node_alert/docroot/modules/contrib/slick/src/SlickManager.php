<?php

namespace Drupal\slick;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\Cache;
use Drupal\slick\Entity\Slick;
use Drupal\blazy\BlazyManagerBase;
use Drupal\blazy\BlazyManagerInterface;

/**
 * Implements BlazyManagerInterface, SlickManagerInterface.
 */
class SlickManager extends BlazyManagerBase implements BlazyManagerInterface, SlickManagerInterface {

  /**
   * The supported skins.
   *
   * @var array
   */
  private static $skins = [
    'browser',
    'overlay',
    'main',
    'thumbnail',
    'arrows',
    'dots',
    'widget',
  ];

  /**
   * Static cache for the skin definition.
   *
   * @var array
   */
  protected $skinDefinition;

  /**
   * Returns the supported skins.
   */
  public static function getConstantSkins() {
    return self::$skins;
  }

  /**
   * Returns slick skins registered via hook_slick_skins_info(), or defaults.
   *
   * @see \Drupal\blazy\BlazyManagerBase::buildSkins()
   */
  public function getSkins() {
    if (!isset($this->skinDefinition)) {
      $methods = ['skins', 'arrows', 'dots'];
      $this->skinDefinition = $this->buildSkins('slick', '\Drupal\slick\SlickSkin', $methods);
    }

    return $this->skinDefinition;
  }

  /**
   * Returns available slick skins by group.
   */
  public function getSkinsByGroup($group = '', $option = FALSE) {
    $skins         = $groups = $ungroups = [];
    $nav_skins     = in_array($group, ['arrows', 'dots']);
    $defined_skins = $nav_skins ? $this->getSkins()[$group] : $this->getSkins()['skins'];

    foreach ($defined_skins as $skin => $properties) {
      $item = $option ? Html::escape($properties['name']) : $properties;
      if (!empty($group)) {
        if (isset($properties['group'])) {
          if ($properties['group'] != $group) {
            continue;
          }
          $groups[$skin] = $item;
        }
        elseif (!$nav_skins) {
          $ungroups[$skin] = $item;
        }
      }
      $skins[$skin] = $item;
    }

    return $group ? array_merge($ungroups, $groups) : $skins;
  }

  /**
   * Implements hook_library_info_build().
   */
  public function libraryInfoBuild() {
    $libraries['slick.css'] = [
      'dependencies' => ['slick/slick'],
      'css' => [
        'theme' => ['/libraries/slick/slick/slick-theme.css' => []],
      ],
    ];

    foreach (self::getConstantSkins() as $group) {
      if ($skins = $this->getSkinsByGroup($group)) {
        foreach ($skins as $key => $skin) {
          $provider = isset($skin['provider']) ? $skin['provider'] : 'slick';
          $id = $provider . '.' . $group . '.' . $key;

          foreach (['css', 'js', 'dependencies'] as $property) {
            if (isset($skin[$property]) && is_array($skin[$property])) {
              $libraries[$id][$property] = $skin[$property];
            }
          }
        }
      }
    }

    return $libraries;
  }

  /**
   * {@inheritdoc}
   */
  public function attach($attach = []) {
    $attach['slick_css']  = isset($attach['slick_css']) ? $attach['slick_css'] : $this->configLoad('slick_css', 'slick.settings');
    $attach['module_css'] = isset($attach['module_css']) ? $attach['module_css'] : $this->configLoad('module_css', 'slick.settings');

    $load = parent::attach($attach);

    if (!empty($attach['lazy'])) {
      $load['library'][] = 'blazy/loading';
    }

    // @todo: Only load slick if not static grid.
    if (is_file('libraries/easing/jquery.easing.min.js')) {
      $load['library'][] = 'slick/slick.easing';
    }

    $load['library'][] = 'slick/slick.load';

    $components = ['colorbox', 'mousewheel'];
    foreach ($components as $component) {
      if (!empty($attach[$component])) {
        $load['library'][] = 'slick/slick.' . $component;
      }
    }

    if (!empty($attach['skin'])) {
      $this->attachSkin($load, $attach);
    }

    // Attach default JS settings to allow responsive displays have a lookup,
    // excluding wasted/trouble options, e.g.: PHP string vs JS object.
    $excludes = explode(' ', 'mobileFirst appendArrows appendDots asNavFor prevArrow nextArrow respondTo');
    $excludes = array_combine($excludes, $excludes);
    $load['drupalSettings']['slick'] = array_diff_key(Slick::defaultSettings(), $excludes);

    $this->moduleHandler->alter('slick_attach_load_info', $load, $attach);
    return $load;
  }

  /**
   * Provides skins only if required.
   */
  public function attachSkin(array &$load, $attach = []) {
    if ($attach['slick_css']) {
      $load['library'][] = 'slick/slick.css';
    }

    if ($attach['module_css']) {
      $load['library'][] = 'slick/slick.theme';
    }

    if (!empty($attach['thumbnail_effect'])) {
      $load['library'][] = 'slick/slick.thumbnail.' . $attach['thumbnail_effect'];
    }

    if (!empty($attach['down_arrow'])) {
      $load['library'][] = 'slick/slick.arrow.down';
    }

    foreach (self::getConstantSkins() as $group) {
      $skin = $group == 'main' ? $attach['skin'] : (isset($attach['skin_' . $group]) ? $attach['skin_' . $group] : '');
      if (!empty($skin)) {
        $skins = $this->getSkinsByGroup($group);
        $provider = isset($skins[$skin]['provider']) ? $skins[$skin]['provider'] : 'slick';
        $load['library'][] = 'slick/' . $provider . '.' . $group . '.' . $skin;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function slick(array $build = []) {
    foreach (['items', 'options', 'optionset', 'settings'] as $key) {
      $build[$key] = isset($build[$key]) ? $build[$key] : [];
    }

    if (empty($build['items'])) {
      return [];
    }

    $slick = [
      '#theme'      => 'slick',
      '#items'      => [],
      '#build'      => $build,
      '#pre_render' => [static::class . '::preRenderSlick'],
    ];

    $settings = $build['settings'];
    if (isset($settings['cache'])) {
      $suffixes[]        = count($build['items']);
      $suffixes[]        = count(array_filter($settings));
      $suffixes[]        = $settings['cache'];
      $cache['contexts'] = ['languages'];
      $cache['max-age']  = $settings['cache'];
      $cache['keys']     = isset($settings['cache_metadata']['keys']) ? $settings['cache_metadata']['keys'] : [$settings['id']];
      $cache['keys'][]   = $settings['display'];
      $cache['tags']     = Cache::buildTags('slick:' . $settings['id'], $suffixes, '.');

      if (!empty($settings['cache_tags'])) {
        $cache['tags'] = Cache::mergeTags($cache['tags'], $settings['cache_tags']);
      }

      $slick['#cache'] = $cache;
    }

    return $slick;
  }

  /**
   * Builds the Slick instance as a structured array ready for ::renderer().
   */
  public static function preRenderSlick(array $element) {
    $build = $element['#build'];
    unset($element['#build']);

    $settings = &$build['settings'];
    if (empty($build['items'])) {
      return [];
    }

    // Adds helper class if thumbnail on dots hover provided.
    $dots_class = [];
    if (!empty($settings['thumbnail_style']) && !empty($settings['thumbnail_effect'])) {
      $dots_class[] = 'slick-dots--thumbnail-' . $settings['thumbnail_effect'];
    }

    // Adds dots skin modifier class if provided.
    if (!empty($settings['skin_dots'])) {
      $dots_class[] = Html::cleanCssIdentifier('slick-dots--' . $settings['skin_dots']);
    }

    if ($dots_class && !empty($build['optionset'])) {
      $dots_class[] = $build['optionset']->getSetting('dotsClass') ?: 'slick-dots';
      $js['dotsClass'] = implode(" ", $dots_class);
    }

    // Overrides common options to re-use an optionset.
    if ($settings['display'] == 'main') {
      if (!empty($settings['override'])) {
        foreach ($settings['overridables'] as $key => $override) {
          $js[$key] = empty($override) ? FALSE : TRUE;
        }
      }

      // Build the Slick grid if provided.
      if (!empty($settings['grid']) && !empty($settings['visible_items'])) {
        $build['items'] = self::buildGrid($build['items'], $settings);
      }
    }

    $build['options'] = isset($js) ? array_merge($build['options'], $js) : $build['options'];
    foreach (['items', 'options', 'optionset', 'settings'] as $key) {
      $element["#$key"] = $build[$key];
    }

    return $element;
  }

  /**
   * Returns items as a grid display.
   */
  public static function buildGrid(array $items = [], array &$settings = []) {
    $grids = [];

    // Enforces unslick with less items.
    if (empty($settings['unslick']) && !empty($settings['count'])) {
      $settings['unslick'] = $settings['count'] < $settings['visible_items'];
    }

    // Display all items if unslick is enforced for plain grid to lightbox.
    // Or when the total is less than visible_items.
    if (!empty($settings['unslick'])) {
      $settings['display']      = 'main';
      $settings['current_item'] = 'grid';
      $settings['count']        = 2;

      $slide['slide'] = [
        '#theme'    => 'slick_grid',
        '#items'    => $items,
        '#delta'    => 0,
        '#settings' => $settings,
      ];
      $slide['settings'] = $settings;
      $grids[0] = $slide;
    }
    else {
      // Otherwise do chunks to have a grid carousel, and also update count.
      $preserve_keys     = !empty($settings['preserve_keys']);
      $grid_items        = array_chunk($items, $settings['visible_items'], $preserve_keys);
      $settings['count'] = count($grid_items);

      foreach ($grid_items as $delta => $grid_item) {
        $slide = [];
        $slide['slide'] = [
          '#theme'    => 'slick_grid',
          '#items'    => $grid_item,
          '#delta'    => $delta,
          '#settings' => $settings,
        ];
        $slide['settings'] = $settings;
        $grids[] = $slide;
        unset($slide);
      }
    }
    return $grids;
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $build = []) {
    foreach (['items', 'options', 'optionset', 'settings'] as $key) {
      $build[$key] = isset($build[$key]) ? $build[$key] : [];
    }

    return empty($build['items']) ? [] : [
      '#theme'      => 'slick_wrapper',
      '#items'      => [],
      '#build'      => $build,
      '#pre_render' => [[$this, 'preRenderSlickWrapper']],
      // Satisfy CTools blocks as per 2017/04/06: 2804165 which expects children
      // only, but not #theme, #type, #markup properties.
      // @todo: Remove when CTools is more accommodative.
      'items'       => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function preRenderSlickWrapper($element) {
    $build = $element['#build'];
    unset($element['#build']);

    if (empty($build['items'])) {
      return [];
    }

    // One slick_theme() to serve multiple displays: main, overlay, thumbnail.
    $defaults = Slick::htmlSettings();
    $settings = $build['settings'] ? array_merge($defaults, $build['settings']) : $defaults;
    $id       = isset($settings['id']) ? $settings['id'] : '';
    $id       = Slick::getHtmlId('slick', $id);
    $thumb_id = $id . '-thumbnail';
    $options  = $build['options'];
    $switch   = isset($settings['media_switch']) ? $settings['media_switch'] : '';

    // Supports programmatic options defined within skin definitions to allow
    // addition of options with other libraries integrated with Slick without
    // modifying optionset such as for Zoom, Reflection, Slicebox, Transit, etc.
    if (!empty($settings['skin'])) {
      $skins = $this->getSkinsByGroup('main');
      if (isset($skins[$settings['skin']]['options'])) {
        $options = array_merge($options, $skins[$settings['skin']]['options']);
      }
    }

    // Additional settings.
    $build['optionset'] = $build['optionset'] ?: Slick::load($settings['optionset']);

    // Ensures deleted optionset while being used doesn't screw up.
    if (empty($build['optionset'])) {
      $build['optionset'] = Slick::load('default');
    }

    $settings['count']    = empty($settings['count']) ? count($build['items']) : $settings['count'];
    $settings['id']       = $id;
    $settings['nav']      = isset($settings['nav']) ? $settings['nav'] : (!empty($settings['optionset_thumbnail']) && isset($build['items'][1]));
    $settings['navpos']   = !empty($settings['nav']) && !empty($settings['thumbnail_position']);
    $settings['vertical'] = $build['optionset']->getSetting('vertical');
    $mousewheel           = $build['optionset']->getSetting('mouseWheel');

    if ($settings['nav']) {
      $options['asNavFor']     = "#{$thumb_id}-slider";
      $optionset_thumbnail     = Slick::load($settings['optionset_thumbnail']);
      $mousewheel              = $optionset_thumbnail->getSetting('mouseWheel');
      $settings['vertical_tn'] = $optionset_thumbnail->getSetting('vertical');
    }

    // Attach libraries.
    if ($switch && $switch != 'content') {
      $settings[$switch] = $switch;
    }

    $settings['mousewheel'] = $mousewheel;
    $settings['down_arrow'] = $build['optionset']->getSetting('downArrow');
    $settings['lazy']       = empty($settings['lazy']) ? $build['optionset']->getSetting('lazyLoad') : $settings['lazy'];
    $settings['blazy']      = empty($settings['blazy']) ? $settings['lazy'] == 'blazy' : $settings['blazy'];
    $attachments            = $this->attach($settings);
    $build['options']       = $options;
    $build['settings']      = $settings;

    // Build the Slick wrapper elements.
    $element['#settings'] = $settings;
    $element['#attached'] = empty($build['attached']) ? $attachments : NestedArray::mergeDeep($build['attached'], $attachments);

    // Build the main Slick.
    $slick[0] = self::slick($build);

    // Build the thumbnail Slick.
    if ($settings['nav'] && isset($build['thumb'])) {
      foreach (['items', 'options', 'settings'] as $key) {
        $build[$key] = isset($build['thumb'][$key]) ? $build['thumb'][$key] : [];
      }

      $settings                     = array_merge($settings, $build['settings']);
      $settings['optionset']        = $settings['optionset_thumbnail'];
      $settings['skin']             = isset($settings['skin_thumbnail']) ? $settings['skin_thumbnail'] : '';
      $settings['display']          = 'thumbnail';
      $build['optionset']           = $optionset_thumbnail;
      $build['settings']            = $settings;
      $build['options']['asNavFor'] = "#{$id}-slider";

      unset($build['thumb']);
      $slick[1] = self::slick($build);
    }

    // Reverse slicks if thumbnail position is provided to get CSS float work.
    if ($settings['navpos']) {
      $slick = array_reverse($slick);
    }

    // Collect the slick instances.
    $element['#items'] = $slick;
    return $element;
  }

}
