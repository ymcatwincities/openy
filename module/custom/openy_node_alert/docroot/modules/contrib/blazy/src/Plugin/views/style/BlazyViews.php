<?php

namespace Drupal\blazy\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\style\StylePluginBase;
use Drupal\blazy\BlazyManagerInterface;
use Drupal\blazy\Dejavu\BlazyDefault;
use Drupal\blazy\BlazyGrid;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Blazy style plugin.
 */
class BlazyViews extends StylePluginBase {

  /**
   * {@inheritdoc}
   */
  protected $usesRowPlugin = TRUE;

  /**
   * {@inheritdoc}
   */
  protected $usesGrouping = FALSE;

  /**
   * The blazy manager service.
   *
   * @var \Drupal\blazy\BlazyManagerInterface
   */
  protected $blazyManager;

  /**
   * Constructs a BlazyManager object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, BlazyManagerInterface $blazy_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->blazyManager = $blazy_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('blazy.manager'));
  }

  /**
   * Returns the blazy admin.
   */
  public function admin() {
    return \Drupal::service('blazy.admin');
  }

  /**
   * Returns the blazy manager.
   */
  public function blazyManager() {
    return $this->blazyManager;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = [];
    foreach (BlazyDefault::gridSettings() as $key => $value) {
      $options[$key] = ['default' => $value];
    }
    return $options + parent::defineOptions();
  }

  /**
   * Overrides StylePluginBase::buildOptionsForm().
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $definition = [
      'namespace' => 'blazy',
      'grid_form' => TRUE,
      'settings'  => $this->options,
      'style'     => TRUE,
      'form_opening_classes' => 'form--blazy form--slick form--views form--half has-tooltip',
    ];

    // Build the form.
    $this->admin()->openingForm($form, $definition);
    $this->admin()->gridForm($form, $definition);

    if (isset($form['grid'])) {
      $form['grid']['#description'] = $this->t('The amount of block grid columns for large monitors 64.063em.');
    }

    $this->admin()->finalizeForm($form, $definition);

    // Blazy doesn't need complex grid with multiple groups.
    unset($form['layout'], $form['preserve_keys'], $form['grid_header'], $form['visible_items'], $form['style']['#empty_option'], $form['grid']['#empty_option']);
  }

  /**
   * Overrides StylePluginBase::render().
   */
  public function render() {
    $settings = $this->options;

    $settings['count']             = count($this->view->result);
    $settings['current_view_mode'] = $this->view->current_display;
    $settings['item_id']           = 'content';
    $settings['namespace']         = 'blazy';
    $settings['view_name']         = $this->view->storage->id();

    $elements = [];
    foreach ($this->renderGrouping($this->view->result, $settings['grouping']) as $rows) {
      $items = [];
      foreach ($rows as $index => $row) {
        $this->view->row_index = $index;

        $items[$index] = $this->view->rowPlugin->render($row);
      }

      // Supports Blazy formatter multi-breakpoint images if available.
      $this->blazyManager->isBlazy($settings, $items[0]);
      $elements = BlazyGrid::build($items, $settings);
      $elements['#attached'] = $this->blazyManager->attach($settings);

      unset($this->view->row_index, $items);
    }

    return $elements;
  }

}
