<?php

/**
 * @file
 * Contains \Drupal\lazyloader\Form\LazyLoaderAdminExclude.
 */

namespace Drupal\lazyloader\Form;

use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Render\Element;
use Drupal\node\Entity\NodeType;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LazyLoaderAdminExclude extends ConfigFormBase {

  /**
   * @var \Drupal\Core\Config\Config|\Drupal\Core\Config\ImmutableConfig
   *   $configuration The LazyLoader exclude configuration entity.
   */
  protected $configuration;

  /**
   * @var \Drupal\Component\Plugin\PluginManagerInterface
   */
  protected $conditionManager;

  /**
   * Constructs a \Drupal\lazyloader\Form\LazyLoaderAdminConfigure object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Component\Plugin\PluginManagerInterface $condition_manager
   *   The condition manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, PluginManagerInterface $condition_manager) {
    parent::__construct($config_factory);
    $this->configuration = $this->config('lazyloader.exclude');
    $this->conditionManager = $condition_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.condition')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'lazyloader_admin_exclude';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('lazyloader.exclude')
      ->set('filenames', $form_state->get('filenames'));

    // Submit visibility condition settings.
    $visibility = $this->config('lazyloader.exclude')->get('visibility') ?: [];
    foreach ($form_state->getValue('visibility') as $condition_id => $values) {
      // Allow the condition to submit the form.
      $condition = $form_state->get(['conditions', $condition_id]);
      $condition_values = (new FormState())
        ->setValues($values);
      $condition->submitConfigurationForm($form, $condition_values);
      if ($condition instanceof ContextAwarePluginInterface) {
        $context_mapping = isset($values['context_mapping']) ? $values['context_mapping'] : [];
        $condition->setContextMapping($context_mapping);
      }
      // Update the original form values.
      $condition_configuration = $condition->getConfiguration();
      $form_state->setValue(['visibility', $condition_id], $condition_configuration);
      // Update the visibility conditions on the block.
      $visibility[$condition_id] = $condition_configuration;
    }
    $this->config('lazyloader.exclude')
      ->set('visibility', $visibility)
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['lazyloader.exclude'];
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];

    $form['visibility'] = [
      '#tree' => TRUE,
    ];
    $form['visibility'] += $this->buildVisibilityInterface([], $form_state);

    $form['filenames'] = [
      '#type' => 'textarea',
      '#title' => t('Exclude images by filename'),
      '#default_value' => $this->configuration->get('filenames'),
      '#description' => t('Any filenames entered in this field will be excluded from lazyloading. Enter one filename per line.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $this->validateVisibility($form, $form_state);
  }

  /**
   * Helper function for building the visibility UI form.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form array with the visibility UI added in.
   */
  protected function buildVisibilityInterface(array $form, FormStateInterface $form_state) {
    $form['visibility_tabs'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->t('Visibility'),
      '#parents' => ['visibility_tabs'],
      '#attached' => [
        'library' => [
          'block/drupal.block',
        ],
      ],
    ];
    // @todo Allow list of conditions to be configured in
    //   https://www.drupal.org/node/2284687.
    $visibility = $this->config('lazyloader.exclude')->get('visibility');
    foreach ($this->conditionManager->getDefinitions() as $condition_id => $definition) {
      /** @var \Drupal\Core\Condition\ConditionInterface $condition */
      $condition = $this->conditionManager->createInstance($condition_id, isset($visibility[$condition_id]) ? $visibility[$condition_id] : []);
      $form_state->set(['conditions', $condition_id], $condition);
      $condition_form = $condition->buildConfigurationForm([], $form_state);
      $condition_form['#type'] = 'details';
      $condition_form['#title'] = $condition->getPluginDefinition()['label'];
      $condition_form['#group'] = 'visibility_tabs';
      $form[$condition_id] = $condition_form;
    }

    if (isset($form['node_type'])) {
      $form['node_type']['#title'] = $this->t('Content types');
      $form['node_type']['bundles']['#title'] = $this->t('Content types');
      $form['node_type']['negate']['#type'] = 'value';
      $form['node_type']['negate']['#title_display'] = 'invisible';
      $form['node_type']['negate']['#value'] = $form['node_type']['negate']['#default_value'];
    }
    if (isset($form['user_role'])) {
      $form['user_role']['#title'] = $this->t('Roles');
      unset($form['user_role']['roles']['#description']);
      $form['user_role']['negate']['#type'] = 'value';
      $form['user_role']['negate']['#value'] = $form['user_role']['negate']['#default_value'];
    }
    if (isset($form['request_path'])) {
      $form['request_path']['#title'] = $this->t('Pages');
      $form['request_path']['negate']['#type'] = 'radios';
      $form['request_path']['negate']['#default_value'] = (int) $form['request_path']['negate']['#default_value'];
      $form['request_path']['negate']['#title_display'] = 'invisible';
      $form['request_path']['negate']['#options'] = [
        $this->t('Show for the listed pages'),
        $this->t('Hide for the listed pages'),
      ];
    }
    if (isset($form['language'])) {
      $form['language']['negate']['#type'] = 'value';
      $form['language']['negate']['#value'] = $form['language']['negate']['#default_value'];
    }
    return $form;
  }

  /**
   * Helper function to independently validate the visibility UI.
   *
   * @param array $form
   *   A nested array form elements comprising the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  protected function validateVisibility(array $form, FormStateInterface $form_state) {
    // Validate visibility condition settings.
    foreach ($form_state->getValue('visibility') as $condition_id => $values) {
      // All condition plugins use 'negate' as a Boolean in their schema.
      // However, certain form elements may return it as 0/1. Cast here to
      // ensure the data is in the expected type.
      if (array_key_exists('negate', $values)) {
        $values['negate'] = (bool) $values['negate'];
      }

      // Allow the condition to validate the form.
      $condition = $form_state->get(['conditions', $condition_id]);
      $condition_values = (new FormState())
        ->setValues($values);
      $condition->validateConfigurationForm($form, $condition_values);
      // Update the original form values.
      $form_state->setValue(['visibility', $condition_id], $condition_values->getValues());
    }
  }

}
