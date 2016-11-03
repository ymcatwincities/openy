<?php

namespace Drupal\tango_card\Form;

use Drupal\Core\Url;
use Drupal\Core\Cache\CacheTagsInvalidator;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\tango_card\TangoCardWrapper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides Tango Card settings form.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The cache tags invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidator
   */
  protected $cacheTagsInvalidator;

  /**
   * Construct SettingsForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   * @param \Drupal\Core\Cache\CacheTagsInvalidator $cache_tags_invalidator
   *   The cache tags invalidator.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, CacheTagsInvalidator $cache_tags_invalidator) {
    $this->entityTypeManager = $entity_type_manager;
    $this->cacheTagsInvalidator = $cache_tags_invalidator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('cache_tags.invalidator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tango_card_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['tango_card.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('tango_card.settings');

    $form['app_mode'] = array(
      '#title' => $this->t('Application mode'),
      '#type' => 'radios',
      '#options' => array(
        'sandbox' => $this->t('Sandbox'),
        'production' => $this->t('Production'),
      ),
      '#default_value' => $config->get('app_mode'),
      '#required' => TRUE,
    );

    $fields = array(
      'platform_name' => 'Platform name',
      'platform_key' => 'Platform key',
    );

    foreach ($fields as $field => $title) {
      $form[$field] = array(
        '#type' => 'textfield',
        '#title' => $this->t($title),
        '#default_value' => $config->get($field),
        '#required' => TRUE,
      );
    }

    $fields = array(
      'account' => 'Default account',
      'campaign' => 'Default campaign',
    );

    foreach ($fields as $field => $title) {
      $entity_type = 'tango_card_' . $field;

      if ($entity = $config->get($field)) {
        $entity = $this->entityTypeManager->getStorage($entity_type)->load($entity);
      }

      $form[$field] = array(
        '#type' => 'entity_autocomplete',
        '#title' => $this->t($title),
        '#target_type' => $entity_type,
        '#default_value' => $entity,
      );
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->cacheTagsInvalidator->invalidateTags(array('tango_card'));

    $fields = array(
      'campaign',
      'account',
      'app_mode',
      'platform_name',
      'platform_key',
    );

    $config = $this->configFactory()->getEditable('tango_card.settings');
    foreach ($fields as $field) {
      $config->set($field, $form_state->getValue($field));
    }

    $config->save();
    parent::submitForm($form, $form_state);
  }

}
