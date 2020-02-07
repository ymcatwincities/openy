<?php

namespace Drupal\openy_google_search\Form;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Settings Form for openy_google_search.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The cache tags invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface
   */
  protected $cacheTagsInvalidator;

  /**
   * Constructs a \Drupal\openy_google_search\Form\SettingsForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cache_tags_invalidator
   *   The cache tag invalidator.
   */
  public function __construct(ConfigFactoryInterface $config_factory, CacheTagsInvalidatorInterface $cache_tags_invalidator) {
    $this->setConfigFactory($config_factory);
    $this->cacheTagsInvalidator = $cache_tags_invalidator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('cache_tags.invalidator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'openy_google_search_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'openy_google_search.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('openy_google_search.settings');

    $form['google_engine_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Google Search Engine ID'),
      '#size' => 40,
      '#default_value' => !empty($config->get('google_engine_id')) ? $config->get('google_engine_id') : '',
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $config = $this->config('openy_google_search.settings');
    $config->set('google_engine_id', $values['google_engine_id']);
    $config->save();

    $this->cacheTagsInvalidator->invalidateTags($config->getCacheTags());

    parent::submitForm($form, $form_state);
  }

}
