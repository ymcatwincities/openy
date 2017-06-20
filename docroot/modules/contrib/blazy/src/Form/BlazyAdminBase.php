<?php

namespace Drupal\blazy\Form;

use Drupal\Core\Url;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Render\Element;
use Drupal\Core\Entity\EntityDisplayRepositoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Component\Utility\Html;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\blazy\BlazyManagerInterface;

/**
 * A base for blazy admin integration to have re-usable methods in one place.
 *
 * @see \Drupal\gridstack\Form\GridStackAdmin
 * @see \Drupal\mason\Form\MasonAdmin
 * @see \Drupal\slick\Form\SlickAdmin
 * @see \Drupal\blazy\Form\BlazyAdminFormatterBase
 */
abstract class BlazyAdminBase implements BlazyAdminInterface {

  use StringTranslationTrait;

  /**
   * A state that represents the responsive image style is disabled.
   */
  const STATE_RESPONSIVE_IMAGE_STYLE_DISABLED = 0;

  /**
   * A state that represents the media switch lightbox is enabled.
   */
  const STATE_LIGHTBOX_ENABLED = 1;

  /**
   * A state that represents the media switch iframe is enabled.
   */
  const STATE_IFRAME_ENABLED = 2;

  /**
   * A state that represents the thumbnail style is enabled.
   */
  const STATE_THUMBNAIL_STYLE_ENABLED = 3;

  /**
   * A state that represents the custom lightbox caption is enabled.
   */
  const STATE_LIGHTBOX_CUSTOM = 4;

  /**
   * A state that represents the image rendered switch is enabled.
   */
  const STATE_IMAGE_RENDERED_ENABLED = 5;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface
   */
  protected $entityDisplayRepository;

  /**
   * The typed config manager service.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface
   */
  protected $typedConfig;

  /**
   * The blazy manager service.
   *
   * @var \Drupal\blazy\BlazyManagerInterface
   */
  protected $blazyManager;

  /**
   * Constructs a BlazyAdminBase object.
   *
   * @param \Drupal\Core\Entity\EntityDisplayRepositoryInterface $entity_display_repository
   *   The entity display repository.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config
   *   The typed config service.
   * @param \Drupal\slick\BlazyManagerInterface $blazy_manager
   *   The blazy manager service.
   */
  public function __construct(EntityDisplayRepositoryInterface $entity_display_repository, TypedConfigManagerInterface $typed_config, BlazyManagerInterface $blazy_manager) {
    $this->entityDisplayRepository = $entity_display_repository;
    $this->typedConfig             = $typed_config;
    $this->blazyManager            = $blazy_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity_display.repository'), $container->get('config.typed'), $container->get('blazy.manager'));
  }

  /**
   * Returns the entity display repository.
   */
  public function getEntityDisplayRepository() {
    return $this->entityDisplayRepository;
  }

  /**
   * Returns the typed config.
   */
  public function getTypedConfig() {
    return $this->typedConfig;
  }

  /**
   * Returns the blazy manager.
   */
  public function blazyManager() {
    return $this->blazyManager;
  }

  /**
   * Returns shared form elements across field formatter and Views.
   */
  public function openingForm(array &$form, $definition = []) {
    if (!isset($definition['namespace'])) {
      return;
    }

    $this->blazyManager->getModuleHandler()->alter('blazy_form_element_definition', $definition);

    // Display style: column, plain static grid, slick grid, slick carousel.
    // https://drafts.csswg.org/css-multicol
    if (!empty($definition['style'])) {
      $form['style'] = [
        '#type'          => 'select',
        '#title'         => $this->t('Display style'),
        '#description'   => $this->t('Either <strong>CSS3 Columns</strong> (experimental pure CSS Masonry) or <strong>Grid Foundation</strong> requires <strong>Grid</strong>. Difference: <strong>Columns</strong> is best with irregular image sizes (scale width, empty height), affects the natural order of grid items. <strong>Grid</strong> with regular cropped ones. Unless required, leave empty to use default formatter, or style.'),
        '#enforced'      => TRUE,
        '#empty_option'  => '- None -',
        '#options'       => [
          'column' => $this->t('CSS3 Columns'),
          'grid'   => $this->t('Grid Foundation'),
        ],
        '#weight'             => -112,
        '#wrapper_attributes' => ['class' => ['form-item--style', 'form-item--tooltip-bottom']],
      ];
    }

    if (!empty($definition['skins'])) {
      $form['skin'] = [
        '#type'        => 'select',
        '#title'       => $this->t('Skin'),
        '#options'     => $definition['skins'],
        '#enforced'    => TRUE,
        '#description' => $this->t('Skins allow various layouts with just CSS. Some options below depend on a skin. Leave empty to DIY. Or use the provided hook_info() and implement the skin interface to register ones.'),
        '#weight'      => -107,
      ];
    }

    if (!empty($definition['background'])) {
      $form['background'] = [
        '#type'        => 'checkbox',
        '#title'       => $this->t('Use CSS background'),
        '#description' => $this->t('Check this to turn the image into CSS background. This opens up the goodness of CSS, such as background cover, fixed attachment, etc. <br /><strong>Important!</strong> Requires a consistent Aspect ratio, otherwise collapsed containers. Unless a min-height is added manually to <strong>.media--background</strong> selector. Not compatible with Responsive image.'),
        '#weight'      => -98,
      ];
    }

    if (!empty($definition['layouts'])) {
      $form['layout'] = [
        '#type'        => 'select',
        '#title'       => $this->t('Layout'),
        '#options'     => $definition['layouts'],
        '#description' => $this->t('Requires a skin. The builtin layouts affects the entire items uniformly. Leave empty to DIY.'),
        '#weight'      => 2,
      ];
    }

    if (!empty($definition['captions'])) {
      $form['caption'] = [
        '#type'        => 'checkboxes',
        '#title'       => $this->t('Caption fields'),
        '#options'     => $definition['captions'],
        '#description' => $this->t('Enable any of the following fields as captions. These fields are treated and wrapped as captions.'),
        '#weight'      => 80,
        '#attributes'  => ['class' => ['form-wrapper--caption']],
      ];
    }

    if (!empty($definition['target_type']) && !empty($definition['view_mode'])) {
      $form['view_mode'] = $this->baseForm($definition)['view_mode'];
    }

    $weight = -99;
    foreach (Element::children($form) as $key) {
      if (!isset($form[$key]['#weight'])) {
        $form[$key]['#weight'] = ++$weight;
      }
    }
  }

  /**
   * Defines re-usable breakpoints form.
   *
   * @see https://html.spec.whatwg.org/multipage/embedded-content.html#attr-img-srcset
   * @see http://ericportis.com/posts/2014/srcset-sizes/
   * @see http://www.sitepoint.com/how-to-build-responsive-images-with-srcset/
   */
  public function breakpointsForm(array &$form, $definition = []) {
    $settings = isset($definition['settings']) ? $definition['settings'] : [];
    $title    = $this->t('Leave Breakpoints empty to disable multi-serving images. <small>If provided, Blazy lazyload applies. Ignored if core Responsive image is provided.<br /> If only two is needed, simply leave the rest empty. At any rate, the last should target the largest monitor. <br />It uses <strong>max-width</strong>, not <strong>min-width</strong>.</small>');

    $form['sizes'] = [
      '#type'               => 'textfield',
      '#title'              => $this->t('Sizes'),
      '#description'        => $this->t('E.g.: (min-width: 1290px) 1290px, 100vw. Use sizes to implement different size image (different height, width) on different screen sizes along with the <strong>w (width)</strong> descriptor below. Ignored by Responsive image.'),
      '#weight'             => 114,
      '#attributes'         => ['class' => ['form-text--sizes', 'js-expandable']],
      '#wrapper_attributes' => ['class' => ['form-item--sizes']],
      '#prefix'             => '<h2 class="form__title form__title--breakpoints">' . $title . '</h2>',
    ];

    $form['breakpoints'] = [
      '#type'       => 'table',
      '#tree'       => TRUE,
      '#header'     => [
        $this->t('Breakpoint'),
        $this->t('Image style'),
        $this->t('Max-width/Descriptor'),
      ],
      '#attributes' => ['class' => ['form-wrapper--table', 'form-wrapper--table-breakpoints']],
      '#weight'     => 115,
      '#enforced'   => TRUE,
    ];

    // Unlike D7, D8 form states seem to not recognize individual field form.
    $vanilla = ':input[name$="[vanilla]"]';
    if (isset($definition['field_name'])) {
      $vanilla = ':input[name="fields[' . $definition['field_name'] . '][settings_edit_form][settings][vanilla]"]';
    }

    if (!empty($definition['_views'])) {
      $vanilla = ':input[name="options[settings][vanilla]"]';
    }

    $breakpoints = $this->breakpointElements($definition);
    foreach ($breakpoints as $breakpoint => $elements) {
      foreach ($elements as $key => $element) {
        $form['breakpoints'][$breakpoint][$key] = $element;

        if (isset($definition['vanilla'])) {
          $form['breakpoints'][$breakpoint][$key]['#states']['enabled'][$vanilla] = ['checked' => FALSE];
        }
        $value = isset($settings['breakpoints'][$breakpoint][$key]) ? $settings['breakpoints'][$breakpoint][$key] : '';
        $form['breakpoints'][$breakpoint][$key]['#default_value'] = $value;
      }
    }
  }

  /**
   * Defines re-usable breakpoints form.
   */
  public function breakpointElements($definition = []) {
    if (!isset($definition['breakpoints'])) {
      return [];
    }

    foreach ($definition['breakpoints'] as $breakpoint) {
      $form[$breakpoint]['breakpoint'] = [
        '#type'               => 'item',
        '#markup'             => $breakpoint,
        '#weight'             => 1,
        '#wrapper_attributes' => ['class' => ['form-item--right']],
      ];

      $form[$breakpoint]['image_style'] = [
        '#type'               => 'select',
        '#title'              => $this->t('Image style'),
        '#title_display'      => 'invisible',
        '#options'            => image_style_options(FALSE),
        '#empty_option'       => $this->t('- None -'),
        '#weight'             => 2,
        '#wrapper_attributes' => ['class' => ['form-item--left']],
      ];

      $form[$breakpoint]['width'] = [
        '#type'               => 'textfield',
        '#title'              => $this->t('Width'),
        '#title_display'      => 'invisible',
        '#description'        => $this->t('See <strong>XS</strong> for detailed info.'),
        '#maz_length'         => 32,
        '#size'               => 6,
        '#weight'             => 3,
        '#attributes'         => ['class' => ['form-text--width', 'js-expandable']],
        '#wrapper_attributes' => ['class' => ['form-item--width']],
      ];

      if ($breakpoint == 'xs') {
        $form[$breakpoint]['width']['#description'] = $this->t('E.g.: <strong>640</strong>, or <strong>2x</strong>, or for <strong>small devices</strong> may be combined into <strong>640w 2x</strong> where <strong>x (pixel density)</strong> descriptor is used to define the device-pixel ratio, and <strong>w (width)</strong> descriptor is the width of image source and works in tandem with <strong>sizes</strong> attributes. Use <strong>w (width)</strong> if any issue/ unsure. Default to <strong>w</strong> if no descriptor provided for backward compatibility.');
      }
    }

    return $form;
  }

  /**
   * Returns re-usable grid elements across field formatter and Views.
   */
  public function gridForm(array &$form, $definition = []) {
    $range = range(1, 12);
    $grid_options = array_combine($range, $range);

    $header = $this->t('Group individual items as block grid<small>Depends on the <strong>Display style</strong>.</small>');
    $form['grid_header'] = [
      '#type'   => 'item',
      '#markup' => '<h3 class="form__title form__title--grid">' . $header . '</h3>',
    ];

    $form['grid'] = [
      '#type'        => 'select',
      '#title'       => $this->t('Grid large'),
      '#options'     => $grid_options,
      '#description' => $this->t('Select <strong>- None -</strong> first if trouble with changing form states. The amount of block grid columns for large monitors 64.063em+. <br /><strong>Requires</strong>:<ol><li>Visible items,</li><li>Skin Grid for starter,</li><li>A reasonable amount of contents.</li></ol>Leave empty to DIY, or to not build grids.'),
      '#enforced'    => TRUE,
    ];

    $form['grid_medium'] = [
      '#type'        => 'select',
      '#title'       => $this->t('Grid medium'),
      '#options'     => $grid_options,
      '#description' => $this->t('The amount of block grid columns for medium devices 40.063em - 64em.'),
    ];

    $form['grid_small'] = [
      '#type'        => 'select',
      '#title'       => $this->t('Grid small'),
      '#options'     => $grid_options,
      '#description' => $this->t('The amount of block grid columns for small devices 0 - 40em. Specific to <strong>CSS3 Columns</strong>, only 1 - 2 column is respected due to small real estate at smallest device.'),
    ];

    $form['visible_items'] = [
      '#type'        => 'select',
      '#title'       => $this->t('Visible items'),
      '#options'     => array_combine(range(1, 32), range(1, 32)),
      '#description' => $this->t('How many items per display at a time.'),
    ];

    $form['preserve_keys'] = [
      '#type'        => 'checkbox',
      '#title'       => $this->t('Preserve keys'),
      '#description' => $this->t('If checked, keys will be preserved. Default is FALSE which will reindex the grid chunk numerically.'),
    ];

    $grids = [
      'grid_header',
      'grid_medium',
      'grid_small',
      'visible_items',
      'preserve_keys',
    ];

    foreach ($grids as $key) {
      $form[$key]['#enforced'] = TRUE;
      $form[$key]['#states'] = [
        'visible' => [
          'select[name$="[grid]"]' => ['!value' => ''],
        ],
      ];
    }
  }

  /**
   * Returns shared ending form elements across field formatter and Views.
   */
  public function closingForm(array &$form, $definition = []) {
    if (isset($definition['current_view_mode'])) {
      $form['current_view_mode'] = [
        '#type'          => 'hidden',
        '#default_value' => isset($definition['current_view_mode']) ? $definition['current_view_mode'] : '_custom',
        '#weight'        => 120,
      ];
    }

    $this->finalizeForm($form, $definition);
  }

  /**
   * Returns simple form elements common for Views field, EB widget, formatters.
   */
  public function baseForm($definition = []) {
    $settings      = isset($definition['settings']) ? $definition['settings'] : [];
    $lightboxes    = $this->blazyManager->getLightboxes();
    $image_styles  = image_style_options(FALSE);
    $is_responsive = function_exists('responsive_image_get_image_dimensions') && !empty($definition['responsive_image']);

    $form = [];
    if (empty($definition['no_image_style'])) {
      $form['image_style'] = [
        '#type'        => 'select',
        '#title'       => $this->t('Image style'),
        '#options'     => $image_styles,
        '#description' => $this->t('The content image style. This will be treated as the fallback image, which is normally smaller, if Breakpoints are provided. Otherwise this is the only image displayed.'),
        '#weight'      => -100,
      ];
    }

    if (isset($settings['media_switch'])) {
      $form['media_switch'] = [
        '#type'         => 'select',
        '#title'        => $this->t('Media switcher'),
        '#options'      => [
          'content' => $this->t('Image linked to content'),
        ],
        '#empty_option' => $this->t('- None -'),
        '#description'  => $this->t('May depend on the enabled supported or supportive modules: colorbox, photobox etc. Be sure to add Thumbnail style if using Photobox. Try selecting "<strong>- None -</strong>" first before changing if trouble with this complex form states.'),
        '#weight'       => -99,
      ];

      // Optional lightbox integration.
      if (!empty($lightboxes)) {
        foreach ($lightboxes as $lightbox) {
          $form['media_switch']['#options'][$lightbox] = $this->t('Image to @lightbox', ['@lightbox' => $lightbox]);
        }

        // Re-use the same image style for both lightboxes.
        $form['box_style'] = [
          '#type'    => 'select',
          '#title'   => $this->t('Lightbox image style'),
          '#options' => $image_styles,
          '#states'  => $this->getState(static::STATE_LIGHTBOX_ENABLED, $definition),
          '#weight'  => -99,
        ];

        if (!empty($definition['multimedia'])) {
          $form['box_media_style'] = [
            '#type'        => 'select',
            '#title'       => $this->t('Lightbox video style'),
            '#options'     => $image_styles,
            '#description' => $this->t('Allows different lightbox video dimensions. Or can be used to have a swipable video if Blazy PhotoSwipe installed.'),
            '#states'      => $this->getState(static::STATE_LIGHTBOX_ENABLED, $definition),
            '#weight'      => -99,
          ];
        }
      }

      // Adds common supported entities for media integration.
      if (!empty($definition['multimedia'])) {
        $form['media_switch']['#options']['media'] = $this->t('Image to iFrame');
      }

      // http://en.wikipedia.org/wiki/List_of_common_resolutions
      $ratio = ['1:1', '3:2', '4:3', '8:5', '16:9', 'fluid', 'enforced'];
      if (empty($definition['no_ratio'])) {
        $form['ratio'] = [
          '#type'         => 'select',
          '#title'        => $this->t('Aspect ratio'),
          '#options'      => array_combine($ratio, $ratio),
          '#empty_option' => $this->t('- None -'),
          '#description'  => $this->t('Aspect ratio to get consistently responsive images and iframes. And to fix layout reflow and excessive height issues. <a href="@dimensions"   target="_blank">Image styles and video dimensions</a> must <a href="@follow" target="_blank">follow the aspect ratio</a>. If not, images will be distorted. Choose <strong>fluid</strong> if unsure. Choose <strong>enforced</strong> if you can stick to one aspect ratio and want multi-serving, or Responsive images. <a href="@link" target="_blank">Learn more</a>, or leave empty to DIY, or when working with multi-image-style plugin like GridStack. <br /><strong>Note!</strong> Only compatible with Blazy multi-serving images, but not Responsive image.', [
            '@dimensions'  => '//size43.com/jqueryVideoTool.html',
            '@follow'      => '//en.wikipedia.org/wiki/Aspect_ratio_%28image%29',
            '@link'        => '//www.smashingmagazine.com/2014/02/27/making-embedded-content-work-in-responsive-design/',
          ]),
          '#weight'        => -96,
        ];

        if ($is_responsive) {
          $form['ratio']['#states'] = $this->getState(static::STATE_RESPONSIVE_IMAGE_STYLE_DISABLED, $definition);
        }
      }
    }

    if (!empty($definition['target_type']) && !empty($definition['view_mode'])) {
      $form['view_mode'] = [
        '#type'        => 'select',
        '#options'     => $this->getViewModeOptions($definition['target_type']),
        '#title'       => $this->t('View mode'),
        '#description' => $this->t('Required to grab the fields, or to have custom entity display as fallback display. If it has fields, be sure the selected "View mode" is enabled, and the enabled fields here are not hidden there. Manage view modes on the <a href=":view_modes">View modes page</a>.', [':view_modes' => Url::fromRoute('entity.entity_view_mode.collection')->toString()]),
        '#weight'      => -96,
        '#enforced'    => TRUE,
      ];
    }

    if (!empty($definition['thumbnail_style'])) {
      $form['thumbnail_style'] = [
        '#type'        => 'select',
        '#title'       => $this->t('Thumbnail style'),
        '#options'     => image_style_options(TRUE),
        '#description' => $this->t('Usages: Photobox/PhotoSwipe thumbnail, or custom work with thumbnails. Leave empty to not use thumbnails.'),
        '#weight'      => -100,
      ];
    }

    $this->blazyManager->getModuleHandler()->alter('blazy_base_form_element', $form, $definition);

    return $form;
  }

  /**
   * Returns re-usable media switch form elements.
   */
  public function mediaSwitchForm(array &$form, $definition = []) {
    $settings   = isset($definition['settings']) ? $definition['settings'] : [];
    $lightboxes = $this->blazyManager->getLightboxes();
    $is_token   = function_exists('token_theme');

    if (empty($definition['media_switch_form'])) {
      return;
    }

    if (isset($settings['media_switch'])) {
      $form['media_switch'] = $this->baseForm($definition)['media_switch'];
      $form['media_switch']['#prefix'] = '<h3 class="form__title form__title--media-switch">' . $this->t('Media switcher') . '</h3>';

      if (empty($definition['no_ratio'])) {
        $form['ratio'] = $this->baseForm($definition)['ratio'];
      }
    }

    if (!empty($definition['multimedia']) && empty($definition['no_iframe_lazy'])) {
      $form['iframe_lazy'] = [
        '#type'        => 'checkbox',
        '#title'       => $this->t('Lazy iframe'),
        '#description' => $this->t('Check to make the video/audio iframes truly lazyloaded, and speed up loading time. Depends on JS enabled at client side. <a href=":more" target="_blank">Read more</a> to <a href=":url" target="_blank">decide</a>.', [':more' => '//goo.gl/FQLFQ6', ':url' => '//goo.gl/f78pMl']),
        '#weight'      => -96,
        '#states'      => $this->getState(static::STATE_IFRAME_ENABLED, $definition),
      ];
    }

    // Optional lightbox integration.
    if (!empty($lightboxes) && isset($settings['media_switch'])) {
      $form['box_style'] = $this->baseForm($definition)['box_style'];

      if (!empty($definition['multimedia'])) {
        $form['box_media_style'] = $this->baseForm($definition)['box_media_style'];
      }

      $box_captions = [
        'auto'         => $this->t('Automatic'),
        'alt'          => $this->t('Alt text'),
        'title'        => $this->t('Title text'),
        'alt_title'    => $this->t('Alt and Title'),
        'title_alt'    => $this->t('Title and Alt'),
        'entity_title' => $this->t('Content title'),
        'custom'       => $this->t('Custom'),
      ];

      if (!empty($definition['box_captions'])) {
        $form['box_caption'] = [
          '#type'        => 'select',
          '#title'       => $this->t('Lightbox caption'),
          '#options'     => $box_captions,
          '#weight'      => -99,
          '#states'      => $this->getState(static::STATE_LIGHTBOX_ENABLED, $definition),
          '#description' => $this->t('Automatic will search for Alt text first, then Title text. Try selecting <strong>- None -</strong> first when changing if trouble with form states.'),
        ];

        $form['box_caption_custom'] = [
          '#title'       => $this->t('Lightbox custom caption'),
          '#type'        => 'textfield',
          '#weight'      => -99,
          '#states'      => $this->getState(static::STATE_LIGHTBOX_CUSTOM, $definition),
          '#description' => $this->t('Multi-value rich text field will be mapped to each image by its delta.'),
        ];

        if ($is_token) {
          $types = isset($definition['entity_type']) ? [$definition['entity_type']] : [];
          $types = isset($definition['target_type']) ? array_merge($types, [$definition['target_type']]) : $types;
          $form['box_caption_custom']['#field_suffix'] = [
            '#theme'       => 'token_tree_link',
            '#text'        => $this->t('Tokens'),
            '#token_types' => $types,
          ];
        }
        else {
          $form['box_caption_custom']['#description'] .= ' ' . $this->t('Install Token module to browse available tokens.');
        }
      }
    }

    $this->blazyManager->getModuleHandler()->alter('blazy_media_switch_form_element', $form, $definition);
  }

  /**
   * Returns re-usable logic, styling and assets across fields and Views.
   */
  public function finalizeForm(array &$form, $definition = []) {
    $namespace = isset($definition['namespace']) ? $definition['namespace'] : 'slick';
    $settings = isset($definition['settings']) ? $definition['settings'] : [];
    $vanilla = isset($definition['vanilla']) ? ' form--vanilla' : '';
    $captions = empty($definition['captions']) ? 0 : count($definition['captions']);
    $wide = $captions > 2 ? ' form--wide form--caption-' . $captions : ' form--caption-' . $captions;
    $fallback = $namespace == 'slick' ? 'form--slick' : 'form--' . $namespace . ' form--slick';
    $classes = isset($definition['form_opening_classes'])
      ? $definition['form_opening_classes']
      : $fallback . ' form--half has-tooltip' . $wide . $vanilla;

    if (!empty($definition['field_type'])) {
      $classes .= ' form--' . str_replace('_', '-', $definition['field_type']);
    }

    $form['opening'] = [
      '#markup' => '<div class="' . $classes . '">',
      '#weight' => -120,
    ];

    $form['closing'] = [
      '#markup' => '</div>',
      '#weight' => 120,
    ];

    $admin_css = isset($definition['admin_css']) ? $definition['admin_css'] : '';
    $admin_css = $admin_css ?: $this->blazyManager->configLoad('admin_css', 'blazy.settings');

    // @todo: Check if needed: 'button', 'container', 'submit'.
    $excludes = ['details', 'fieldset', 'hidden', 'markup', 'item', 'table'];
    $selects  = ['cache', 'optionset', 'view_mode'];

    foreach (Element::children($form) as $key) {
      if (isset($form[$key]['#type']) && !in_array($form[$key]['#type'], $excludes)) {
        if (!isset($form[$key]['#default_value']) && isset($settings[$key])) {
          $value = is_array($settings[$key]) ? array_values((array) $settings[$key]) : $settings[$key];
          $form[$key]['#default_value'] = $value;
        }
        if (!isset($form[$key]['#attributes']) && isset($form[$key]['#description'])) {
          $form[$key]['#attributes'] = ['class' => ['is-tooltip']];
        }

        if ($admin_css) {
          if ($form[$key]['#type'] == 'checkbox' && $form[$key]['#type'] != 'checkboxes') {
            $form[$key]['#field_suffix'] = '&nbsp;';
            $form[$key]['#title_display'] = 'before';
          }
          elseif ($form[$key]['#type'] == 'checkboxes' && !empty($form[$key]['#options'])) {
            foreach ($form[$key]['#options'] as $i => $option) {
              $form[$key][$i]['#field_suffix'] = '&nbsp;';
              $form[$key][$i]['#title_display'] = 'before';
            }
          }
        }

        if ($form[$key]['#type'] == 'select' && !in_array($key, $selects)) {
          if (!isset($form[$key]['#empty_option']) && !isset($form[$key]['#required'])) {
            $form[$key]['#empty_option'] = $this->t('- None -');
          }
        }

        if (!isset($form[$key]['#enforced']) && isset($definition['vanilla']) && isset($form[$key]['#type'])) {
          $states['visible'][':input[name*="[vanilla]"]'] = ['checked' => FALSE];
          if (isset($form[$key]['#states'])) {
            $form[$key]['#states']['visible'][':input[name*="[vanilla]"]'] = ['checked' => FALSE];
          }
          else {
            $form[$key]['#states'] = $states;
          }
        }
      }

      $form[$key]['#wrapper_attributes']['class'][] = 'form-item--' . str_replace('_', '-', $key);

      if (isset($form[$key]['#access']) && $form[$key]['#access'] == FALSE) {
        unset($form[$key]['#default_value']);
      }
    }

    if ($admin_css) {
      $form['closing']['#attached']['library'][] = 'blazy/admin';
    }

    $this->blazyManager->getModuleHandler()->alter('blazy_complete_form_element', $form, $definition);
  }

  /**
   * Returns time in interval for select options.
   */
  public function getCacheOptions() {
    $period = [
      0,
      60,
      180,
      300,
      600,
      900,
      1800,
      2700,
      3600,
      10800,
      21600,
      32400,
      43200,
      86400,
    ];
    $period = array_map([\Drupal::service('date.formatter'), 'formatInterval'], array_combine($period, $period));
    $period[0] = '<' . $this->t('No caching') . '>';
    return $period + [Cache::PERMANENT => $this->t('Permanent')];
  }

  /**
   * Returns available optionsets for select options.
   */
  public function getOptionsetOptions($entity_type = '') {
    $optionsets = [];
    if (empty($entity_type)) {
      return $optionsets;
    }

    $entities = $this->blazyManager->entityLoadMultiple($entity_type);
    foreach ((array) $entities as $entity) {
      $optionsets[$entity->id()] = Html::escape($entity->label());
    }
    asort($optionsets);
    return $optionsets;
  }

  /**
   * Returns available view modes for select options.
   */
  public function getViewModeOptions($target_type) {
    return $this->entityDisplayRepository->getViewModeOptions($target_type);
  }

  /**
   * Get one of the pre-defined states used in this form.
   *
   * Thanks to SAM152 at colorbox.module for the little sweet idea.
   *
   * @param string $state
   *   The state to get that matches one of the state class constants.
   *
   * @return array
   *   A corresponding form API state.
   */
  protected function getState($state, $definition = []) {
    $lightboxes = [];
    foreach ($this->blazyManager->getLightboxes() as $key => $lightbox) {
      $lightboxes[$key]['value'] = $lightbox;
    }

    $states = [
      static::STATE_RESPONSIVE_IMAGE_STYLE_DISABLED => [
        'visible' => [
          'select[name$="[responsive_image_style]"]' => ['value' => ''],
        ],
      ],
      static::STATE_LIGHTBOX_ENABLED => [
        'visible' => [
          'select[name*="[media_switch]"]' => $lightboxes,
        ],
      ],
      static::STATE_LIGHTBOX_CUSTOM => [
        'visible' => [
          'select[name$="[box_caption]"]' => ['value' => 'custom'],
          'select[name*="[media_switch]"]' => $lightboxes,
        ],
      ],
      static::STATE_IFRAME_ENABLED => [
        'visible' => [
          'select[name*="[media_switch]"]' => ['value' => 'media'],
        ],
      ],
      static::STATE_THUMBNAIL_STYLE_ENABLED => [
        'visible' => [
          'select[name$="[thumbnail_style]"]' => ['!value' => ''],
        ],
      ],
      static::STATE_IMAGE_RENDERED_ENABLED => [
        'visible' => [
          'select[name$="[media_switch]"]' => ['!value' => 'rendered'],
        ],
      ],
    ];
    return $states[$state];
  }

}
