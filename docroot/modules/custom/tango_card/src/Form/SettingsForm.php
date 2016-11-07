<?php

namespace Drupal\tango_card\Form;

use Drupal\Core\Link;
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

    $form['app_mode'] = [
      '#title' => $this->t('Application mode'),
      '#type' => 'radios',
      '#options' => [
        'sandbox' => $this->t('Sandbox'),
        'production' => $this->t('Production'),
      ],
      '#default_value' => $config->get('app_mode'),
      '#required' => TRUE,
    ];

    $fields = [
      'platform_name' => 'Platform name',
      'platform_key' => 'Platform key',
    ];

    foreach ($fields as $field => $title) {
      $form[$field] = [
        '#type' => 'textfield',
        '#title' => $this->t($title),
        '#default_value' => $config->get($field),
        '#required' => TRUE,
      ];
    }

    $link_title = $this->t('here');
    $fields = [
      'account' => [
        'title' => 'Default Tango Card account',
        'description' => 'The default Tango Account to use on requests. To create an account, click !here.',
      ],
      'campaign' => [
        'title' => 'Default campaign',
        'description' => 'The default campaign to use on requests. A campaign contains settings like email template and notification message. To create a campaign, click !here.',
      ],
    ];

    foreach ($fields as $field => $info) {
      $entity_type = 'tango_card_' . $field;

      if ($entity = $config->get($field)) {
        $entity = $this->entityTypeManager->getStorage($entity_type)->load($entity);
      }

      $link = new Link($link_title, Url::fromRoute('entity.' . $entity_type . '.add_form'));
      $args = ['!here' => $link->toString()];

      $form[$field] = [
        '#type' => 'entity_autocomplete',
        '#title' => $this->t($info['title']),
        '#target_type' => $entity_type,
        '#default_value' => $entity,
        '#description' => $this->t($info['description'], $args),
      ];
    }

    if (!$form['platform_key']['#default_value']) {
      $form['account']['#disabled'] = TRUE;
      $form['account']['#attributes'] = ['title' => $this->t('You must set platform credentials above before create an account.')];
      $form['account']['#description'] = $this->t('The default Tango Account to use on requests.');
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->cacheTagsInvalidator->invalidateTags(['tango_card']);

    $fields = [
      'campaign',
      'account',
      'app_mode',
      'platform_name',
      'platform_key',
    ];

    $config = $this->configFactory()->getEditable('tango_card.settings');
    foreach ($fields as $field) {
      $config->set($field, $form_state->getValue($field));
    }

    $config->save();
    parent::submitForm($form, $form_state);
  }

}
