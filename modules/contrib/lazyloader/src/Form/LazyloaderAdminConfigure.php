<?php

/**
 * @file
 * Contains \Drupal\lazyloader\Form\LazyLoaderAdminConfigure.
 */

namespace Drupal\lazyloader\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Theme\Registry;
use Drupal\Core\Utility\ThemeRegistry;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LazyLoaderAdminConfigure extends ConfigFormBase implements ContainerInjectionInterface {

  /**
   * The theme registry.
   *
   * @var \Drupal\Core\Theme\Registry
   */
  protected $themeRegistry;

  /**
   * Constructs a \Drupal\lazyloader\Form\LazyLoaderAdminConfigure object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Theme\Registry $theme_registry
   *   The theme registry.
   */
  public function __construct(ConfigFactoryInterface $config_factory, Registry $theme_registry) {
    parent::__construct($config_factory);

    $this->themeRegistry = $theme_registry;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('theme.registry')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'lazyloader_admin_configure';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('lazyloader.configuration');
    foreach (Element::children($form) as $variable) {
      $config->set($variable, $form_state->getValue($form[$variable]['#parents']));
    }
    $config->save();

    // Rebuild the theme registry if the module was enabled/disabled.
    if ($form['enabled']['#default_value'] !== $form_state->getValue(['enabled'])) {
      $this->themeRegistry->reset();
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['lazyloader.configuration'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];
    $config = $this->config('lazyloader.configuration');
    $form['enabled'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enabled'),
      '#default_value' =>$config->get('enabled'),
      '#description' => $this->t('Enable/Disable Lazyloader (Useful for testing)'),
    ];

    $form['debugging'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use development javascript'),
      '#default_value' => $config->get('debugging'),
      '#description' => $this->t('By default lazyloader will use the minified version of the lazysizes library. By checking this option it will use the non-minified version instead.'),
    ];

    $form['cdn'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Serve javascript from CDN'),
      '#default_value' => $config->get('cdn'),
      '#description' => $this->t('Serve the lazyloading script from a CDN instead of your own server'),
    ];

    $form['placeholder'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Placeholder Image'),
      '#default_value' => $config->get('placeholder'),
      '#description' => $this->t('Path to your placeholder image, ex. sites/default/files/placeholder_image.gif. Leave it blank to use the default image.'),
    ];

    return parent::buildForm($form, $form_state);
  }

}
