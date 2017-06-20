<?php

namespace Drupal\blazy\Plugin\views\field;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\blazy\Dejavu\BlazyDefault;
use Drupal\blazy\BlazyManagerInterface;
use Drupal\blazy\Dejavu\BlazyEntityTrait;
use Drupal\blazy\Dejavu\BlazyVideoTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a base views field plugin to render a preview of supported fields.
 */
abstract class BlazyViewsFieldPluginBase extends FieldPluginBase {

  use BlazyEntityTrait;
  use BlazyVideoTrait;

  /**
   * The blazy service manager.
   *
   * @var \Drupal\blazy\BlazyManagerInterface
   */
  protected $blazyManager;

  /**
   * Constructs a BlazyViewsFieldPluginBase object.
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
  public function blazyAdmin() {
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
    $options = parent::defineOptions();

    foreach ($this->getDefaultValues() as $key => $default) {
      $options[$key] = ['default' => $default];
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $definitions = $this->getScopedFormElements();

    $form += $this->blazyAdmin()->baseForm($definitions);

    foreach ($this->getDefaultValues() as $key => $default) {
      if (isset($form[$key])) {
        $form[$key]['#default_value'] = isset($this->options[$key]) ? $this->options[$key] : $default;
        $form[$key]['#weight'] = 0;
        if (in_array($key, ['box_style', 'box_media_style'])) {
          $form[$key]['#empty_option'] = $this->t('- None -');
        }
      }
    }

    if (isset($form['view_mode'])) {
      $form['view_mode']['#description'] = $this->t('Will fallback to this view mode, else entity label.');
    }
    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Do nothing -- to override the parent query.
  }

  /**
   * Defines the default values.
   */
  public function getDefaultValues() {
    return [
      'box_style'       => '',
      'box_media_style' => '',
      'image_style'     => '',
      'media_switch'    => 'media',
      'ratio'           => 'fluid',
      'thumbnail_style' => '',
      'view_mode'       => 'default',
    ];
  }

  /**
   * Merges the settings.
   */
  public function mergedViewsSettings() {
    $settings = [];

    // Only fetch what we already asked for.
    foreach ($this->getDefaultValues() as $key => $default) {
      $settings[$key] = isset($this->options[$key]) ? $this->options[$key] : $default;
    }

    $settings['count'] = count($this->view->result);
    $settings['current_view_mode'] = $this->view->current_display;
    $settings['view_name'] = $this->view->storage->id();

    return array_merge(BlazyDefault::entitySettings(), $settings);
  }

  /**
   * Defines the scope for the form elements.
   */
  public function getScopedFormElements() {
    return [
      'settings' => array_filter($this->options),
      'target_type' => $this->view->getBaseEntityType()->id(),
      'thumbnail_style' => TRUE,
    ];
  }

}
