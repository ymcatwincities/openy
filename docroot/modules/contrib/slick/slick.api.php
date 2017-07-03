<?php

/**
 * @file
 * Hooks and API provided by the Slick module.
 *
 * Modules may implement any of the available hooks to interact with Slick.
 */

/**
 * Slick may be configured using the web interface via sub-modules.
 *
 * However below is a few sample coded ones. The simple API is to achieve
 * consistent markups working for various skins, and layouts for both coded
 * and sub-modules implementations.
 *
 * The expected parameters are:
 *   - items: A required array of slick contents: text, image or media.
 *   - options: An optional array of key:value pairs of custom JS options.
 *   - optionset: An optional optionset object to avoid multiple invocations.
 *   - settings: An array of key:value pairs of HTML/layout related settings.
 *
 * @see \Drupal\slick\Plugin\Field\FieldFormatter\SlickImageFormatter
 * @see \Drupal\slick_views\Plugin\views\style\SlickViews
 */

/**
 * Quick sample #1.
 *
 * @see \Drupal\slick\SlickManager::build()
 * @see template_preprocess_slick_wrapper()
 * @see template_preprocess_slick()
 *
 * @return array
 *   The renderable array of a slick instance.
 */
function my_module_render_slick() {
  // Invoke the plugin class, or use a DI service container accordingly.
  $slick = \Drupal::service('slick.manager');

  // Access the formatter service for image-related methods:
  $formatter = \Drupal::service('slick.formatter');

  $build = [];

  // Caption contains: alt, data, link, overlay, title.
  // Each item has keys: slide, caption, settings.
  $items[] = [
    // Use $formatter->getImage($element) to have lazyLoad where $element
    // contains:
    // item: Drupal\image\Plugin\Field\FieldType\ImageItem.
    'slide'   => '<img src="https://drupal.org/files/One.gif" />',
    'caption' => ['title' => t('Description #1')],
  ];

  $items[] = [
    'slide'   => '<img src="https://drupal.org/files/Two.gif" />',
    'caption' => ['title' => t('Description #2')],
  ];

  $items[] = [
    'slide'   => '<img src="https://drupal.org/files/Three.gif" />',
    'caption' => ['title' => t('Description #3')],
  ];

  // Pass the $items to the array.
  $build['items'] = $items;

  // If no optionset name is provided via $build['settings'], slick will
  // fallback to 'default'.
  // Optionally override 'default' optionset with custom JS options.
  $build['options'] = [
    'autoplay' => TRUE,
    'dots'     => TRUE,
    'arrows'   => FALSE,
  ];

  // Build the slick.
  $element = $slick->build($build);

  // Prepare $variables to pass into a .twig.html file.
  $variables['slick'] = $element;

  // Render the slick at a .twig.html file.
  // {{ slick }}
  // Or simply return the $element if a renderable array is expected.
  return $element;
}

/**
 * Detailed sample #2.
 *
 * This can go to some hook_preprocess() of a target html.twig, or any relevant
 * PHP file.
 *
 * The goal is to create a vertical newsticker, or tweets, with pure text only.
 * First, create an unformatted Views block, says 'Ticker' containing ~ 10
 * titles, or any data for the contents -- using EFQ, or static array will do.
 *
 * @return array
 *   The renderable array of a slick instance.
 */
function my_module_render_slick_detail() {
  // Invoke the plugin class, or use a DI service container accordingly.
  $slick = \Drupal::service('slick.manager');

  // Access the formatter service for image related methods:
  $formatter = \Drupal::service('slick.formatter');

  $build = [];

  // 1.
  // Optional $settings, can be removed.
  // Provides HTML settings with optionset name and ID, none of JS related.
  // To add JS key:value pairs, use #options below instead.
  // @see \Drupal\slick\SlickDefault for most supported settings.
  $build['settings'] = [
    // Optional optionset name, otherwise fallback to default.
    // 'optionset' => 'blog',
    // Optional skin name fetched from hook_slick_skins_info(), otherwise none.
    // 'skin' => 'fullwidth',
    // Define the main ID. The rest are managed by the module.
    // If you provide ID, be sure unique per instance as it is cached.
    // Leave empty to be provided by the module.
    'id' => 'slick-ticker',

    // Define the cache max-age, default to -1 (Cache::PERMANENT) to permanently
    // cache the results. Hence a 1 hour is passed. Be sure it is an integer!
    'cache' => 3600,
  ];

  // 3.
  // Obligatory #items, as otherwise empty slick.
  // Prepare #items contents, note the 'slide' key is to hold the actual slide
  // which can be pure and simple text, or any image/media file.
  // Meaning $rows can be text only, or image/audio/video, or a combination
  // of both.
  // To add caption/overlay, use 'caption' key with the supported sub-keys:
  // alt, data, link, overlay, title for complex content.
  // Sanitize each sub-key content accordingly.
  // @see template_preprocess_slick_slide() for more info.
  $items = [];
  foreach ($rows as $key => $row) {
    // Each item has keys: slide, caption, settings.
    $items[] = [
      'slide' => $row,

      // Optional caption contains: alt, data, link, overlay, title.
      // If the above slide is an image, to add text caption, use:
      'caption' => ['title' => 'some-caption data'],

      // Optional slide settings to manipulate layout, can be removed.
      // Individual slide supports some useful settings like layout, classes,
      // etc.
      // Meaning each slide can have different layout, or classes.
      // @see src/Plugin/Field/README.txt
      'settings' => [

        // Optionally add a custom layout, can be a static uniform value, or
        // dynamic one based on the relevant field value.
        // @see src/Plugin/Field/README.txt for the supported layout keys.
        'layout' => 'bottom',

        // Optionally add a custom class, can be a static uniform class, or
        // dynamic one based on the relevant field value.
        'class' => 'slide--custom-class--' . $key,
      ],
    ];
  }

  // Pass the $items to the array.
  $build['items'] = $items;

  // 4.
  // Optional specific JS options, to re-use one optionset, can be removed.
  // Play with speed and options to achieve desired result.
  // @see config/install/slick.optionset.default.yml
  $build['options'] = [
    'arrows'    => FALSE,
    'autoplay'  => TRUE,
    'vertical'  => TRUE,
    'draggable' => FALSE,
  ];

  // 5.
  // Build the slick with the arguments as described above.
  $element = $slick->build($build);

  // Prepare $variables to pass into a .twig.html file.
  $variables['slick'] = $element;

  // Render the slick at a .twig.html file.
  // {{ slick }}
  // Or simply return the $element if a renderable array is expected.
  return $element;
}

/**
 * AsNavFor sample #3.
 *
 * The only requirement for asNavFor is:
 * @code
 *   $build['settings']['optionset'] = 'optionset_name';
 *   $build['settings']['optionset_thumbnail'] = 'optionset_thumbnail_name';
 * @endcode
 *
 * The rest are optional, and will fallback to default:
 *   - $build['settings']['optionset_thumbnail'] = 'optionset_thumbnail_name';
 *     Defined at the main settings.
 *
 *   - $build['settings']['id'] = 'slick-asnavfor';
 *     Only main display ID is needed. The thumbnail ID will be
 *     automatically created: 'slick-asnavfor-thumbnail', including the content
 *     attributes accordingly. If none provided, will fallback to incremented
 *     ID.
 *
 * See the HTML structure below to get a clear idea.
 *
 * 1. Main slider:
 * \n @code
 *   <div id="slick-asnavfor" class="slick">
 *     <div class="slick__slider slick-initialized slick-slider">
 *       <div class="slick__slide"></div>
 *     </div>
 *   </div>
 * @endcode \n
 * 2. Thumbnail slider:
 * \n @code
 *   <div id="slick-asnavfor-thumbnail" class="slick">
 *     <div class="slick__slider slick-initialized slick-slider">
 *       <div class="slick__slide"></div>
 *     </div>
 *   </div>
 * @endcode \n
 * The asnavfor targets are the 'slick-initialized' attributes, and managed by
 * the module automatically when using SlickManager::build().
 *
 * @return array
 *   The renderable array of slick instances.
 */
function my_module_render_slick_asnavfor() {
  // Invoke the plugin class, or use a DI service container accordingly.
  $slick = \Drupal::service('slick.manager');

  // Access the formatter service for image related methods:
  $formatter = \Drupal::service('slick.formatter');

  $build = [];

  // 1. Main slider ------------------------------------------------------------
  // Add the main display items.
  $build['items'] = [];

  // Use theme_slick_image to have lazyLoad, or theme_image_style/theme_image.
  $images = [1, 2, 3, 4, 6, 7];
  foreach ($images as $key) {
    // Each item has keys: slide, caption, settings.
    $build['items'][] = [

      // Use $formatter->getImage($element) to have lazyLoad where $element
      // contains:
      // item: Drupal\image\Plugin\Field\FieldType\ImageItem.
      'slide'   => '<img src="/sites/all/images/image-0' . $key . '.jpg" width="1140" />',

      // Main caption contains: alt, data, link, overlay, title keys which serve
      // the purpose to have consistent markups and skins without bothering much
      // nor remembering what HTML tags and where to place to provide for each
      // purpose cosnsitently. CSS will do layout regardless HTML composition.
      // If having more complex caption data, use 'data' key instead.
      // If the common layout doesn't satisfy the need, just override the twig.
      'caption' => ['title' => 'Description #' . $key],
    ];
  }

  // Optionally override the optionset.
  $build['options'] = [
    'arrows'        => FALSE,
    'centerMode'    => TRUE,
    'centerPadding' => '',
  ];

  // Satisfy the asnavfor main settings.
  // @see \Drupal\slick\SlickDefault for most supported settings.
  $build['settings'] = [
    // The only required is 'optionset_thumbnail'.
    // Define both main and thumbnail optionset names once at the main display.
    'optionset' => 'optionset_main_name',
    'optionset_thumbnail' => 'optionset_thumbnail_name',

    // The rest is optional, just FYI.
    'id' => 'slick-asnavfor',
    'skin' => 'skin-main-name',
    'skin_thumbnail' => 'skin-thumbnail-name',
  ];

  // 2. Thumbnail slider -------------------------------------------------------
  // The thumbnail array is grouped by 'thumb'.
  $build['thumb'] = ['items' => []];
  foreach ($images as $key) {
    // Each item has keys: slide, caption, settings.
    $build['thumb']['items'][] = [
      // Use $formatter->getThumbnail($settings) where $settings contain:
      // uri, image_style, height, width, alt, title.
      'slide'   => '<img src="/sites/all/images/image-0' . $key . '.jpg" width="210" />',

      // Thumbnail caption accepts direct markup or custom renderable array
      // without any special key to be simple as much as complex.
      // Think Youtube playlist with scrolling navigation: thumbnail, text, etc.
      'caption' => ['#markup' => 'Description #' . $key],
    ];
  }

  // Optionally override 'optionset_thumbnail_name' with custom JS options.
  $build['thumb']['options'] = [
    'arrows'        => TRUE,
    'centerMode'    => TRUE,
    'centerPadding' => '10px',

    // Be sure to have multiple slides for the thumbnail, otherwise nonsense.
    'slidesToShow'  => 5,
  ];

  // Build the slick once.
  $element = $slick->build($build);

  // Prepare variables to pass into a .twig.html file.
  $variables['slick'] = $element;

  // Render the slick at a .twig.html file.
  // {{ slick }}
  // Or simply return the $element if a renderable array is expected.
  return $element;
}

/**
 * Implements hook_slick_skins_info().
 *
 * Registers a class that should hold skin definitions and implements
 * \Drupal\slick\SlickSkinInterface.
 *
 * @deprecated, will be removed anytime when a core solution is available.
 * @see #2233261
 * Postponed till D9.
 *
 * @see slick_hook_info()
 * @see slick_example.module
 * @see slick_extras.module
 * @see \Drupal\slick\SlickSkinInterface
 */
function hook_slick_skins_info() {
  return '\Drupal\hook\HookSlickSkin';
}

/**
 * Implements SlickSkinInterface as registered via hook_slick_skins_info().
 *
 * The class must implement \Drupal\slick\SlickSkinInterface, and it has 3
 * supported methods: ::skins(), ::dots(), ::arrows() to have skin options for
 * main/thumbnail/overlay/nested displays, dots, and arrows skins respectively.
 * The declared skins will be available for custom coded, or UI selections.
 */
class HookSlickSkin implements SlickSkinInterface {

  /**
   * {@inheritdoc}
   */
  public function skins() {
    $theme_path = base_path() . drupal_get_path('theme', 'my_theme');

    return [
      'skin_name' => [
        // Human readable skin name.
        'name' => 'Skin name',
        // Description of the skin.
        'description' => t('Skin description.'),
        // To reduce confusion on form selection: main, thumbnail.
        'group' => 'main',
        // Optional module name to prefix the library name.
        'provider' => 'my_module',
        'css' => [
          'theme' => [
            // Full path to a CSS file to include with the skin.
            $theme_path . '/css/my-theme--slider.css' => [],
            $theme_path . '/css/my-theme--carousel.css' => [],
          ],
        ],
        'js' => [
          // Full path to a JS file to include with the skin.
          $theme_path . '/js/my-theme--slider.js' => [],
          $theme_path . '/js/my-theme--carousel.js' => [],
          // If you want to act on afterSlick event, or any other slick events,
          // put a lighter weight before slick.load.min.js (0).
          $theme_path . '/js/slick.skin.menu.min.js' => ['weight' => -2],
        ],
      ],
    ];
  }

  /**
   * Returns the Slick dot skins.
   *
   * The provided dot skins will be available at sub-module UI form.
   * A skin dot named 'hop' will have a class 'slick-dots--hop' for the UL.
   *
   * The array is similar to the self::skins(), excluding group, JS.
   *
   * @return array
   *   The array of the dot skins.
   */
  public function dots() {
    // Create an array of dot skins.
    return [];
  }

  /**
   * Returns the Slick arrow skins.
   *
   * The provided arrow skins will be available at sub-module UI form.
   * A skin arrow 'slit' will have a class 'slick__arrow--slit' for the NAV.
   *
   * The array is similar to the self::skins(), excluding group, JS.
   *
   * @return array
   *   The array of the arrow skins.
   */
  public function arrows() {
    // Create an array of arrow skins.
    return [];
  }

}
