<?php

namespace Drupal\fullcalendar_options\Form;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @todo.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * An array of Fullcalendar Options available to use.
   *
   * @var array
   */
  protected $options;

  /**
   * Constructs a SettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\Component\Plugin\PluginManagerInterface $manager
   */
  public function __construct(ConfigFactoryInterface $config_factory, PluginManagerInterface $manager) {
    parent::__construct($config_factory);

    $instance = $manager->createInstance('fullcalendar_options');
    $this->options = $instance->optionsList();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.fullcalendar')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'fullcalendar_options_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['fullcalendar_options.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('fullcalendar_options.settings');
    $form['fullcalendar_options'] = array(
      '#type' => 'details',
      '#title' => $this->t('Options'),
      '#description' => $this->t('Each setting can be exposed for all views.'),
      '#open' => TRUE,
    );
    foreach ($this->options as $key => $info) {
      $form['fullcalendar_options'][$key] = array(
        '#type' => 'checkbox',
        '#default_value' => $config->get($key),
      ) + $info;
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('fullcalendar_options.settings');
    foreach ($this->options as $key => $info) {
      $config->set($key, $form_state->getValue($key));
    }
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
