<?php

namespace Drupal\slick\Entity;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Slick configuration entity.
 *
 * @ConfigEntityType(
 *   id = "slick",
 *   label = @Translation("Slick optionset"),
 *   list_path = "admin/config/media/slick",
 *   config_prefix = "optionset",
 *   entity_keys = {
 *     "id" = "name",
 *     "label" = "label",
 *     "status" = "status",
 *     "weight" = "weight",
 *   },
 *   config_export = {
 *     "id",
 *     "name",
 *     "label",
 *     "status",
 *     "weight",
 *     "group",
 *     "skin",
 *     "breakpoints",
 *     "optimized",
 *     "options",
 *   }
 * )
 */
class Slick extends ConfigEntityBase implements SlickInterface {

  /**
   * The legacy CTools ID for the configurable optionset.
   *
   * @var string
   */
  protected $name;

  /**
   * The human-readable name for the optionset.
   *
   * @var string
   */
  protected $label;

  /**
   * The weight to re-arrange the order of slick optionsets.
   *
   * @var int
   */
  protected $weight = 0;

  /**
   * The optionset group for easy selections.
   *
   * @var string
   */
  protected $group = '';

  /**
   * The skin name for the optionset.
   *
   * @var string
   */
  protected $skin = '';

  /**
   * The number of breakpoints for the optionset.
   *
   * @var int
   */
  protected $breakpoints = 0;

  /**
   * The flag indicating to optimize the stored options by removing defaults.
   *
   * @var bool
   */
  protected $optimized = FALSE;

  /**
   * The plugin instance options.
   *
   * @var array
   */
  protected $options = [];

  /**
   * The slick HTML ID.
   *
   * @var int
   */
  private static $slickId;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $values, $entity_type = 'slick') {
    parent::__construct($values, $entity_type);
  }

  /**
   * Overrides Drupal\Core\Entity\Entity::id().
   */
  public function id() {
    return $this->name;
  }

  /**
   * {@inheritdoc}
   */
  public function getSkin() {
    return $this->skin;
  }

  /**
   * {@inheritdoc}
   */
  public function getBreakpoints() {
    return $this->breakpoints;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroup() {
    return $this->group;
  }

  /**
   * {@inheritdoc}
   */
  public function optimized() {
    return $this->optimized;
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions($group = NULL, $property = NULL) {
    if ($group) {
      if (is_array($group)) {
        return NestedArray::getValue($this->options, (array) $group);
      }
      elseif (isset($property) && isset($this->options[$group])) {
        return isset($this->options[$group][$property]) ? $this->options[$group][$property] : NULL;
      }
      return $this->options[$group];
    }

    return $this->options;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings() {
    // With the Optimized options, all defaults are cleaned out, merge em.
    return isset($this->options['settings']) ? array_merge(self::defaultSettings(), $this->options['settings']) : self::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function setSettings(array $settings = []) {
    $this->options['settings'] = $settings;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSetting($name) {
    return isset($this->getSettings()[$name]) ? $this->getSettings()[$name] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setSetting($name, $value) {
    $this->options['settings'][$name] = $value;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings($group = 'settings') {
    return self::load('default')->options[$group];
  }

  /**
   * Returns the Slick responsive settings.
   *
   * @return array
   *   The responsive options.
   */
  public function getResponsiveOptions() {
    if (empty($this->breakpoints)) {
      return FALSE;
    }
    $options = [];
    if (isset($this->options['responsives']['responsive'])) {
      $responsives = $this->options['responsives'];
      if ($responsives['responsive']) {
        foreach ($responsives['responsive'] as $delta => $responsive) {
          if (empty($responsives['responsive'][$delta]['breakpoint'])) {
            unset($responsives['responsive'][$delta]);
          }
          if (isset($responsives['responsive'][$delta])) {
            $options[$delta] = $responsive;
          }
        }
      }
    }
    return $options;
  }

  /**
   * Sets the Slick responsive settings.
   *
   * @return $this
   *   The class instance that this method is called on.
   */
  public function setResponsiveSettings($values, $delta = 0, $key = 'settings') {
    $this->options['responsives']['responsive'][$delta][$key] = $values;
    return $this;
  }

  /**
   * Strip out options containing default values so to have real clean JSON.
   *
   * @return array
   *   The cleaned out settings.
   */
  public function removeDefaultValues(array $js) {
    $config   = [];
    $defaults = self::defaultSettings();

    // Remove wasted dependent options if disabled, empty or not.
    $this->removeWastedDependentOptions($js);
    $config = array_diff_assoc($js, $defaults);

    // Remove empty lazyLoad, or left to default ondemand, to avoid JS error.
    if (empty($config['lazyLoad'])) {
      unset($config['lazyLoad']);
    }

    // Do not pass arrows HTML to JSON object as some are enforced.
    $excludes = [
      'downArrow',
      'downArrowTarget',
      'downArrowOffset',
      'prevArrow',
      'nextArrow',
    ];
    foreach ($excludes as $key) {
      unset($config[$key]);
    }

    // Clean up responsive options if similar to defaults.
    if ($responsives = $this->getResponsiveOptions()) {
      $cleaned = [];
      foreach ($responsives as $key => $responsive) {
        $cleaned[$key]['breakpoint'] = $responsives[$key]['breakpoint'];

        // Destroy responsive slick if so configured.
        if (!empty($responsives[$key]['unslick'])) {
          $cleaned[$key]['settings'] = 'unslick';
          unset($responsives[$key]['unslick']);
        }
        else {
          // Remove wasted dependent options if disabled, empty or not.
          $this->removeWastedDependentOptions($responsives[$key]['settings']);
          $cleaned[$key]['settings'] = array_diff_assoc($responsives[$key]['settings'], $defaults);
        }
      }
      $config['responsive'] = $cleaned;
    }
    return $config;
  }

  /**
   * Removes wasted dependent options, even if not empty.
   */
  public function removeWastedDependentOptions(array &$js) {
    foreach (self::getDependentOptions() as $key => $option) {
      if (isset($js[$key]) && empty($js[$key])) {
        foreach ($option as $dependent) {
          unset($js[$dependent]);
        }
      }
    }

    if (!empty($js['useCSS']) && !empty($js['cssEaseBezier'])) {
      $js['cssEase'] = $js['cssEaseBezier'];
    }
    unset($js['cssEaseOverride'], $js['cssEaseBezier']);
  }

  /**
   * Defines the dependent options.
   *
   * @return array
   *   The dependent options.
   */
  public static function getDependentOptions() {
    $down_arrow = ['downArrowTarget', 'downArrowOffset'];
    return [
      'arrows'     => ['prevArrow', 'nextArrow', 'downArrow'] + $down_arrow,
      'downArrow'  => $down_arrow,
      'autoplay'   => ['pauseOnHover', 'pauseOnDotsHover', 'autoplaySpeed'],
      'centerMode' => ['centerPadding'],
      'dots'       => ['dotsClass', 'appendDots'],
      'swipe'      => ['swipeToSlide'],
      'useCSS'     => ['cssEase', 'cssEaseBezier', 'cssEaseOverride'],
      'vertical'   => ['verticalSwiping'],
    ];
  }

  /**
   * Returns the trusted HTML ID of a single slick instance.
   *
   * @return string
   *   The html ID.
   *
   * @todo: Consider Blazy::getHtmlId() instead.
   */
  public static function getHtmlId($string = 'slick', $id = '') {
    if (!isset(static::$slickId)) {
      static::$slickId = 0;
    }

    // Do not use dynamic Html::getUniqueId, otherwise broken asnavfors.
    return empty($id) ? Html::getId($string . '-' . ++static::$slickId) : strip_tags($id);
  }

  /**
   * Returns HTML or layout related settings to shut up notices.
   *
   * @return array
   *   The default settings.
   */
  public static function htmlSettings() {
    return [
      'cache'             => 0,
      'current_view_mode' => '',
      'display'           => 'main',
      'grid'              => 0,
      'id'                => '',
      'nav'               => FALSE,
      'navpos'            => FALSE,
      'media_switch'      => '',
      'optionset'         => 'default',
      'ratio'             => '',
      'skin'              => '',
      'unslick'           => FALSE,
      'vanilla'           => FALSE,
      'vertical'          => FALSE,
      'vertical_tn'       => FALSE,
      'view_name'         => '',
    ];
  }

  /**
   * Defines JS options required by theme_slick(), used with optimized option.
   */
  public static function jsSettings() {
    return [
      'asNavFor'        => '',
      'downArrowTarget' => '',
      'downArrowOffset' => '',
      'lazyLoad'        => 'ondemand',
      'prevArrow'       => '<button type="button" data-role="none" class="slick-prev" aria-label="Previous" tabindex="0" role="button">Previous</button>',
      'nextArrow'       => '<button type="button" data-role="none" class="slick-next" aria-label="Next" tabindex="0" role="button">Next</button>',
      'rows'            => 1,
      'slidesPerRow'    => 1,
      'slide'           => '',
      'slidesToShow'    => 1,
    ];
  }

}
