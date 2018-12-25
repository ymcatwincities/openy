<?php

namespace Drupal\slick_views\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;
use Drupal\blazy\Blazy;
use Drupal\blazy\BlazyManagerInterface;
use Drupal\blazy\Dejavu\BlazyStylePluginBase;
use Drupal\slick\SlickDefault;
use Drupal\slick\SlickManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Slick style plugin.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "slick",
 *   title = @Translation("Slick Carousel"),
 *   help = @Translation("Display the results in a Slick carousel."),
 *   theme = "slick_wrapper",
 *   register_theme = FALSE,
 *   display_types = {"normal"}
 * )
 */
class SlickViews extends BlazyStylePluginBase {

  /**
   * The slick service manager.
   *
   * @var \Drupal\slick\SlickManagerInterface
   */
  protected $manager;

  /**
   * Constructs a SlickManager object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, BlazyManagerInterface $blazy_manager, SlickManagerInterface $manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $blazy_manager);
    $this->manager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('blazy.manager'), $container->get('slick.manager'));
  }

  /**
   * Returns the slick admin.
   */
  public function admin() {
    return \Drupal::service('slick.admin');
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = [];
    foreach (SlickDefault::extendedSettings() as $key => $value) {
      $options[$key] = ['default' => $value];
    }
    return $options + parent::defineOptions();
  }

  /**
   * Overrides parent::buildOptionsForm().
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $fields = [
      'captions',
      'classes',
      'images',
      'layouts',
      'links',
      'overlays',
      'thumbnails',
      'thumb_captions',
      'thumb_positions',
      'titles',
    ];

    $definition = $this->getDefinedFieldOptions($fields);
    foreach (['fieldable_form', 'grid_form', 'id', 'nav', 'vanilla'] as $key) {
      $definition[$key] = TRUE;
    }

    $this->admin()->buildSettingsForm($form, $definition);

    $count = count($definition['captions']);
    $wide = $count > 2 ? ' form--wide form--caption-' . $count : ' form--caption-' . $count;
    $title = '<p class="form__header form__title">';
    $title .= $this->t('Check Vanilla if using content/custom markups, not fields. <small>See it under <strong>Format > Show</strong> section. Otherwise slick markups apply which require some fields added below.</small>');
    $title .= '</p>';

    $form['opening']['#markup'] = '<div class="form--slick form--views form--half form--vanilla has-tooltip' . $wide . '">' . $title;

    if (isset($form['image'])) {
      $form['image']['#description'] .= ' ' . $this->t('Use Blazy formatter to have it lazyloaded. Other supported Formatters: Colorbox, Intense, Responsive image, Video Embed Field, Youtube Field.');
    }
    if (isset($form['overlay'])) {
      $form['overlay']['#description'] .= ' ' . $this->t('Be sure to CHECK "<strong>Style settings > Use field template</strong>" _only if using Slick formatter for nested sliders, otherwise keep it UNCHECKED!');
    }
  }

  /**
   * Overrides StylePluginBase::render().
   */
  public function render() {
    $view      = $this->view;
    $count     = count($view->result);
    $settings  = $this->options;
    $view_name = $view->storage->id();
    $view_mode = $view->current_display;
    $id        = Blazy::getHtmlId("slick-views-{$view_name}-{$view_mode}", $settings['id']);

    $settings += [
      'cache_metadata'    => [
        'keys' => [$id, $view_mode, $settings['optionset']],
      ],
      'count'             => $count,
      'current_view_mode' => $view_mode,
      'view_name'         => $view_name,
    ];

    $settings['id']           = $id;
    $settings['item_id']      = 'slide';
    $settings['caption']      = array_filter($settings['caption']);
    $settings['namespace']    = 'slick';
    $settings['nav']          = !$settings['vanilla'] && $settings['optionset_thumbnail'] && isset($view->result[1]);
    $settings['overridables'] = empty($settings['override']) ? array_filter($settings['overridables']) : $settings['overridables'];

    $elements = [];
    foreach ($this->renderGrouping($view->result, $settings['grouping']) as $rows) {
      $build = $this->buildElements($settings, $rows);

      // Supports Blazy formatter multi-breakpoint images if available.
      if (empty($settings['vanilla'])) {
        $this->blazyManager()->isBlazy($settings, $build['items'][0]);
      }

      $build['settings'] = $settings;

      $elements = $this->manager->build($build);
      unset($build);
    }
    return $elements;
  }

  /**
   * Returns slick contents.
   */
  public function buildElements(array $settings, $rows) {
    $build   = [];
    $view    = $this->view;
    $keys    = array_keys($view->field);
    $item_id = $settings['item_id'];

    // @todo enable after proper checks.
    // $settings = array_filter($settings);
    foreach ($rows as $index => $row) {
      $view->row_index = $index;

      $slide = [];
      $thumb = $slide[$item_id] = [];

      $slide['settings'] = $settings;

      if (!empty($settings['class'])) {
        $classes = $this->getFieldString($row, $settings['class'], $index);
        $slide['settings']['class'] = empty($classes[$index]) ? [] : $classes[$index];
      }

      // Use Vanilla slick if so configured, ignoring Slick markups.
      if (!empty($settings['vanilla'])) {
        $slide[$item_id] = $view->rowPlugin->render($row);
      }
      else {
        $this->buildElement($slide, $row, $index);

        if (!empty($settings['nav'])) {
          $thumb[$item_id]  = empty($settings['thumbnail']) ? [] : $this->getFieldRendered($index, $settings['thumbnail']);
          $thumb['caption'] = empty($settings['thumbnail_caption']) ? [] : $this->getFieldRendered($index, $settings['thumbnail_caption']);

          $build['thumb']['items'][$index] = $thumb;
        }
      }

      $build['items'][$index] = $slide;
      unset($slide, $thumb);
    }
    unset($view->row_index);
    return $build;
  }

}
