<?php

namespace Drupal\slick\Form;

use Drupal\Core\Url;
use Drupal\Core\Render\Element;
use Drupal\Component\Utility\Html;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\blazy\Dejavu\BlazyAdminExtended;
use Drupal\slick\SlickManagerInterface;

/**
 * Provides resusable admin functions, or form elements.
 */
class SlickAdmin implements SlickAdminInterface {

  use StringTranslationTrait;

  /**
   * The blazy admin service.
   *
   * @var \Drupal\blazy\Dejavu\BlazyAdminExtended
   */
  protected $blazyAdmin;

  /**
   * The slick manager service.
   *
   * @var \Drupal\slick\SlickManagerInterface
   */
  protected $manager;

  /**
   * Constructs a SlickAdmin object.
   *
   * @param \Drupal\blazy\Dejavu\BlazyAdminExtended $blazy_admin
   *   The blazy admin service.
   * @param \Drupal\slick\SlickManagerInterface $manager
   *   The slick manager service.
   */
  public function __construct(BlazyAdminExtended $blazy_admin, SlickManagerInterface $manager) {
    $this->blazyAdmin = $blazy_admin;
    $this->manager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static (
      $container->get('blazy.admin.extended'),
      $container->get('slick.manager')
    );
  }

  /**
   * Returns the blazy admin formatter.
   */
  public function blazyAdmin() {
    return $this->blazyAdmin;
  }

  /**
   * Returns the slick manager.
   */
  public function manager() {
    return $this->manager;
  }

  /**
   * Returns the main form elements.
   */
  public function buildSettingsForm(array &$form, $definition = []) {
    $definition['caches']           = isset($definition['caches']) ? $definition['caches'] : TRUE;
    $definition['namespace']        = 'slick';
    $definition['optionsets']       = isset($definition['optionsets']) ? $definition['optionsets'] : $this->getOptionsetsByGroupOptions('main');
    $definition['skins']            = isset($definition['skins']) ? $definition['skins'] : $this->getSkinsByGroupOptions('main');
    $definition['responsive_image'] = isset($definition['responsive_image']) ? $definition['responsive_image'] : TRUE;

    foreach (['optionsets', 'skins'] as $key) {
      if (isset($definition[$key]['default'])) {
        ksort($definition[$key]);
        $definition[$key] = ['default' => $definition[$key]['default']] + $definition[$key];
      }
    }

    if (empty($definition['no_layouts'])) {
      $definition['layouts'] = isset($definition['layouts']) ? array_merge($this->getLayoutOptions(), $definition['layouts']) : $this->getLayoutOptions();
    }

    $this->openingForm($form, $definition);

    if (!empty($definition['image_style_form']) && !isset($form['image_style'])) {
      $this->imageStyleForm($form, $definition);
    }

    if (!empty($definition['media_switch_form']) && !isset($form['media_switch'])) {
      $this->mediaSwitchForm($form, $definition);
    }

    if (!empty($definition['grid_form']) && !isset($form['grid'])) {
      $this->gridForm($form, $definition);
    }

    if (!empty($definition['fieldable_form']) && !isset($form['image'])) {
      $this->fieldableForm($form, $definition);
    }

    if (!empty($definition['breakpoints'])) {
      $this->blazyAdmin->breakpointsForm($form, $definition);
    }

    if (!empty($definition['style']) && isset($form['style']['#description'])) {
      $form['style']['#description'] .= ' ' . $this->t('CSS3 Columns is best with adaptiveHeight, non-vertical. Will use regular carousel as default style if left empty. Yet, both CSS3 Columns and Grid Foundation are respected as Grid displays when <strong>Grid large</strong> option is provided.');
    }

    $this->closingForm($form, $definition);
  }

  /**
   * Returns the opening form elements.
   */
  public function openingForm(array &$form, $definition = []) {
    $path         = drupal_get_path('module', 'slick');
    $readme       = Url::fromUri('base:' . $path . '/README.txt')->toString();
    $readme_field = Url::fromUri('base:' . $path . '/src/Plugin/Field/README.txt')->toString();
    $arrows       = $this->getSkinsByGroupOptions('arrows');
    $dots         = $this->getSkinsByGroupOptions('dots');

    if (!isset($form['optionset'])) {
      $this->blazyAdmin->openingForm($form, $definition);

      $form['optionset']['#title'] = $this->t('Optionset main');

      if ($this->manager()->getModuleHandler()->moduleExists('slick_ui')) {
        $route_name = 'entity.slick.collection';
        $form['optionset']['#description'] = $this->t('Manage optionsets at <a href=":url" target="_blank">the optionset admin page</a>.', [':url' => Url::fromRoute($route_name)->toString()]);
      }
    }

    if (!empty($definition['nav']) || !empty($definition['thumbnails'])) {
      $form['optionset_thumbnail'] = [
        '#type'        => 'select',
        '#title'       => $this->t('Optionset thumbnail'),
        '#options'     => $this->getOptionsetsByGroupOptions('thumbnail'),
        '#description' => $this->t('If provided, asNavFor aka thumbnail navigation applies. Leave empty to not use thumbnail navigation.'),
        '#weight'      => -108,
      ];

      $form['skin_thumbnail'] = [
        '#type'        => 'select',
        '#title'       => $this->t('Skin thumbnail'),
        '#options'     => $this->getSkinsByGroupOptions('thumbnail'),
        '#description' => $this->t('Thumbnail navigation skin. See main <a href="@url" target="_blank">README</a> for details on Skins. Leave empty to not use thumbnail navigation.', ['@url' => $readme]),
        '#weight'      => -106,
      ];
    }

    if (count($arrows) > 0) {
      $form['skin_arrows'] = [
        '#type'        => 'select',
        '#title'       => $this->t('Skin arrows'),
        '#options'     => $arrows ?: [],
        '#enforced'    => TRUE,
        '#description' => $this->t('Implement \Drupal\slick\SlickSkinInterface::arrows() to add your own arrows skins, in the same format as SlickSkinInterface::skins().'),
        '#weight'      => -105,
      ];
    }

    if (count($dots) > 0) {
      $form['skin_dots'] = [
        '#type'        => 'select',
        '#title'       => $this->t('Skin dots'),
        '#options'     => $dots ?: [],
        '#enforced'    => TRUE,
        '#description' => $this->t('Implement \Drupal\slick\SlickSkinInterface::dots() to add your own dots skins, in the same format as SlickSkinInterface::skins().'),
        '#weight'      => -105,
      ];
    }

    if (!empty($definition['thumb_positions'])) {
      $form['thumbnail_position'] = [
        '#type'        => 'select',
        '#title'       => $this->t('Thumbnail position'),
        '#options' => [
          'left'       => $this->t('Left'),
          'right'      => $this->t('Right'),
          'top'        => $this->t('Top'),
          'over-left'  => $this->t('Overlay left'),
          'over-right' => $this->t('Overlay right'),
          'over-top'   => $this->t('Overlay top'),
        ],
        '#description' => $this->t('By default thumbnail is positioned at bottom. Hence to change the position of thumbnail. Only reasonable with 1 visible main stage at a time. Except any TOP, the rest requires Vertical option enabled for Optionset thumbnail, and a custom CSS height to selector <strong>.slick--thumbnail</strong> to avoid overflowing tall thumbnails, or adjust <strong>slidesToShow</strong> to fit the height. Further theming is required as usual. Overlay is absolutely positioned over the stage rather than sharing the space. See skin <strong>X VTabs</strong> for vertical thumbnail sample.'),
        '#states' => [
          'visible' => [
            'select[name*="[optionset_thumbnail]"]' => ['!value' => ''],
          ],
        ],
        '#weight'      => -99,
      ];
    }

    if (!empty($definition['thumb_captions'])) {
      $form['thumbnail_caption'] = [
        '#type'        => 'select',
        '#title'       => $this->t('Thumbnail caption'),
        '#options'     => $definition['thumb_captions'],
        '#description' => $this->t('Thumbnail caption maybe just title/ plain text. If Thumbnail image style is not provided, the thumbnail pagers will be just text like regular tabs.'),
        '#states' => [
          'visible' => [
            'select[name*="[optionset_thumbnail]"]' => ['!value' => ''],
          ],
        ],
        '#weight'      => 2,
      ];
    }

    if (isset($form['skin'])) {
      $form['skin']['#title'] = $this->t('Skin main');
      $form['skin']['#description'] = $this->t('Skins allow various layouts with just CSS. Some options below depend on a skin. However a combination of skins and options may lead to unpredictable layouts, get yourself dirty. E.g.: Skin Split requires any split layout option. Failing to choose the expected layout makes it useless. See <a href=":url" target="_blank">SKINS section at README.txt</a> for details on Skins. Leave empty to DIY. Or use hook_slick_skins_info() and implement \Drupal\slick\SlickSkinInterface to register ones.', [':url' => $readme]);
    }

    if (isset($form['layout'])) {
      $form['layout']['#description'] = $this->t('Requires a skin. The builtin layouts affects the entire slides uniformly. Split half requires any skin Split. See <a href="@url" target="_blank">README</a> under "Slide layout" for more info. Leave empty to DIY.', ['@url' => $readme_field]);
    }

    $weight = -99;
    foreach (Element::children($form) as $key) {
      if (!isset($form[$key]['#weight'])) {
        $form[$key]['#weight'] = ++$weight;
      }
    }
  }

  /**
   * Returns the image formatter form elements.
   */
  public function mediaSwitchForm(array &$form, $definition = []) {
    $this->blazyAdmin->mediaSwitchForm($form, $definition);

    if (isset($form['media_switch'])) {
      $form['media_switch']['#description'] = $this->t('Depends on the enabled supported modules, or has known integration with Slick.<ol><li>Link to content: for aggregated small slicks.</li><li>Image to iframe: audio/video is hidden below image until toggled, otherwise iframe is always displayed, and draggable fails. Aspect ratio applies.</li><li>Colorbox.</li><li>Photobox. Be sure to select "Thumbnail style" for the overlay thumbnails.</li><li>Intense: image to fullscreen intense image.</li>');

      if (!empty($definition['multimedia']) && isset($definition['fieldable_form'])) {
        $form['media_switch']['#description'] .= ' ' . $this->t('<li>Image rendered by its formatter: image-related settings here will be ignored: breakpoints, image style, CSS background, aspect ratio, lazyload, etc. Only choose if needing a special image formatter such as Image Link Formatter.</li>');
      }

      $form['media_switch']['#description'] .= ' ' . $this->t('</ol> Try selecting "<strong>- None -</strong>" first before changing if trouble with this complex form states.');
    }

    if (isset($form['ratio']['#description'])) {
      $form['ratio']['#description'] .= ' ' . $this->t('Required if using media entity to switch between iframe and overlay image, otherwise DIY.');
    }
  }

  /**
   * Returns the image formatter form elements.
   */
  public function imageStyleForm(array &$form, $definition = []) {
    $definition['thumbnail_style'] = isset($definition['thumbnail_style']) ? $definition['thumbnail_style'] : TRUE;
    $definition['ratios'] = isset($definition['ratios']) ? $definition['ratios'] : TRUE;

    $definition['thumbnail_effect'] = [
      'hover' => $this->t('Hoverable'),
      'grid'  => $this->t('Static grid'),
    ];

    if (!isset($form['image_style'])) {
      $this->blazyAdmin->imageStyleForm($form, $definition);

      $form['image_style']['#description'] = $this->t('The main image style. This will be treated as the fallback image, which is normally smaller, if Breakpoints are provided, and if <strong>Use CSS background</strong> is disabled. Otherwise this is the only image displayed. Ignored by Responsive image option.');
    }

    if (isset($form['thumbnail_style'])) {
      $form['thumbnail_style']['#description'] = $this->t('Usages: <ol><li>If <em>Optionset thumbnail</em> provided, it is for asNavFor thumbnail navigation.</li><li>For <em>Thumbnail effect</em>.</li><li>Photobox thumbnail.</li><li>Custom work via the provided data-thumb attributes: arrows with thumbnails, Photoswipe thumbnail, etc.</li></ol>Leave empty to not use thumbnails.');
    }

    if (isset($form['thumbnail_effect'])) {
      $form['thumbnail_effect']['#description'] = $this->t('Dependent on a Skin, Dots and Thumbnail style options. No asnavfor/ Optionset thumbnail is needed. <ol><li><strong>Hoverable</strong>: Dots pager are kept, and thumbnail will be hidden and only visible on dot mouseover, default to min-width 120px.</li><li><strong>Static grid</strong>: Dots are hidden, and thumbnails are displayed as a static grid acting like dots pager.</li></ol>Alternative to asNavFor aka separate thumbnails as slider.');
    }

    if (isset($form['background'])) {
      $form['background']['#description'] .= ' ' . $this->t('Works best with a single visible slide, skins full width/screen.');
    }
  }

  /**
   * Returns re-usable fieldable formatter form elements.
   */
  public function fieldableForm(array &$form, $definition = []) {
    $this->blazyAdmin->fieldableForm($form, $definition);

    if (isset($form['thumbnail'])) {
      $form['thumbnail']['#description'] = $this->t("Only needed if <em>Optionset thumbnail</em> is provided. Maybe the same field as the main image, only different instance and image style. Leave empty to not use thumbnail pager.");
    }

    if (isset($form['overlay'])) {
      $form['overlay']['#title'] = $this->t('Overlay media/slicks');
      $form['overlay']['#description'] = $this->t('For audio/video, be sure the display is not image. For nested slicks, use the Slick carousel formatter for this field. Zebra layout is reasonable for overlay and captions.');
    }
  }

  /**
   * Returns re-usable grid elements across Slick field formatter and Views.
   */
  public function gridForm(array &$form, $definition = []) {
    if (!isset($form['grid'])) {
      $this->blazyAdmin->gridForm($form, $definition);
    }

    $header = $this->t('Group individual item as block grid?<small>An older alternative to core <strong>Rows</strong> option. Only works if the total items &gt; <strong>Visible slides</strong>. <br />block grid != slidesToShow option, yet both can work in tandem.<br />block grid = Rows option, yet the first is module feature, the later core.</small>');

    $form['grid_header']['#markup'] = '<h3 class="form__title form__title--grid">' . $header . '</h3>';

    $form['grid']['#description'] = $this->t('The amount of block grid columns for large monitors 64.063em - 90em. <br /><strong>Requires</strong>:<ol><li>Visible items,</li><li>Skin Grid for starter,</li><li>A reasonable amount of contents,</li><li>Optionset with Rows and slidesPerRow = 1.</li></ol>This is module feature, older than core Rows, and offers more flexibility. Leave empty to DIY, or to not build grids.');
  }

  /**
   * Returns the closing ending form elements.
   */
  public function closingForm(array &$form, $definition = []) {
    $form['override'] = [
      '#title'       => $this->t('Override main optionset'),
      '#type'        => 'checkbox',
      '#description' => $this->t('If checked, the following options will override the main optionset. Useful to re-use one optionset for several different displays.'),
      '#weight'      => 112,
      '#enforced'    => TRUE,
    ];

    $form['overridables'] = [
      '#type'        => 'checkboxes',
      '#title'       => $this->t('Overridable options'),
      '#description' => $this->t("Override the main optionset to re-use one. Anything dictated here will override the current main optionset. Unchecked means FALSE"),
      '#options'     => $this->getOverridableOptions(),
      '#weight'      => 113,
      '#enforced'    => TRUE,
      '#states' => [
        'visible' => [
          ':input[name$="[override]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $this->blazyAdmin->closingForm($form, $definition);
  }

  /**
   * Returns overridable options to re-use one optionset.
   */
  public function getOverridableOptions() {
    $options = [
      'arrows'        => $this->t('Arrows'),
      'autoplay'      => $this->t('Autoplay'),
      'dots'          => $this->t('Dots'),
      'draggable'     => $this->t('Draggable'),
      'infinite'      => $this->t('Infinite'),
      'mouseWheel'    => $this->t('Mousewheel'),
      'randomize'     => $this->t('Randomize'),
      'variableWidth' => $this->t('Variable width'),
    ];

    $this->manager->getModuleHandler()->alter('slick_overridable_options_info', $options);
    return $options;
  }

  /**
   * Returns default layout options for the core Image, or Views.
   */
  public function getLayoutOptions() {
    return [
      'bottom'      => $this->t('Caption bottom'),
      'top'         => $this->t('Caption top'),
      'right'       => $this->t('Caption right'),
      'left'        => $this->t('Caption left'),
      'center'      => $this->t('Caption center'),
      'center-top'  => $this->t('Caption center top'),
      'below'       => $this->t('Caption below the slide'),
      'stage-right' => $this->t('Caption left, stage right'),
      'stage-left'  => $this->t('Caption right, stage left'),
      'split-right' => $this->t('Caption left, stage right, split half'),
      'split-left'  => $this->t('Caption right, stage left, split half'),
      'stage-zebra' => $this->t('Stage zebra'),
      'split-zebra' => $this->t('Split half zebra'),
    ];
  }

  /**
   * Returns available slick optionsets by group.
   */
  public function getOptionsetsByGroupOptions($group = '') {
    $optionsets = $groups = $ungroups = [];
    $slicks = $this->manager->entityLoadMultiple('slick');
    foreach ($slicks as $slick) {
      $name = Html::escape($slick->label());
      $id = $slick->id();
      $current_group = $slick->getGroup();
      if (!empty($group)) {
        if ($current_group) {
          if ($current_group != $group) {
            continue;
          }
          $groups[$id] = $name;
        }
        else {
          $ungroups[$id] = $name;
        }
      }
      $optionsets[$id] = $name;
    }

    return $group ? array_merge($ungroups, $groups) : $optionsets;
  }

  /**
   * Returns available slick skins for select options.
   */
  public function getSkinsByGroupOptions($group = '') {
    return $this->manager->getSkinsByGroup($group, TRUE);
  }

  /**
   * Return the field formatter settings summary.
   *
   * @deprecated: Removed for self::getSettingsSummary().
   */
  public function settingsSummary($plugin, $definition = []) {
    return $this->blazyAdmin->settingsSummary($plugin, $definition);
  }

  /**
   * Return the field formatter settings summary.
   *
   * @todo: Remove second param $plugin for post-release for Blazy RC2+.
   */
  public function getSettingsSummary($definition = [], $plugin = NULL) {
    // @todo: Remove condition for Blazy RC2+.
    if (!method_exists($this->blazyAdmin, 'getSettingsSummary')) {
      return $this->blazyAdmin->settingsSummary($plugin, $definition);
    }

    return $this->blazyAdmin->getSettingsSummary($definition);
  }

  /**
   * Returns available fields for select options.
   */
  public function getFieldOptions($target_bundles = [], $allowed_field_types = [], $entity_type_id = 'media', $target_type = '') {
    return $this->blazyAdmin->getFieldOptions($target_bundles, $allowed_field_types, $entity_type_id, $target_type);
  }

  /**
   * Returns re-usable logic, styling and assets across fields and Views.
   */
  public function finalizeForm(array &$form, $definition = []) {
    $this->blazyAdmin->finalizeForm($form, $definition);
  }

}
