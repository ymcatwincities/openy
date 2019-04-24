<?php

namespace Drupal\openy_addthis\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure AddThis settings for this site.
 */
class OpenyAddThisSettingsForm extends ConfigFormBase implements ContainerInjectionInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The Entity manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config_factory) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'openy_addthis_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['openy_addthis.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('openy_addthis.settings');

    $form['public_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('AddThis public id'),
      '#default_value' => $config->get('public_id'),
      '#required' => TRUE,
      '#placeholder' => 'ra-xxxxxxxxxxxxxxx',
      '#description' => $this->t('Your AddThis public id. Example: 
        ra-xxxxxxxxxxxxxxx. Currently we support only inline type.'),
    ];

    // Load note types.
    $nodeTypes = $this->entityTypeManager->getStorage('node_type')
      ->loadMultiple();

    // Build options list.
    $options = [];
    foreach ($nodeTypes as $machineName => $nodeType) {
      $options[$machineName] = $nodeType->label();
    }

    $form['bundles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Content types'),
      '#description' => $this->t('Select list of content types where AddThis should be enabled by default.'),
      '#options' => $options,
      '#default_value' => $config->get('bundles') ?: [],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Save the config.
    $this->config('openy_addthis.settings')
      ->set('public_id', $form_state->getValue('public_id'))
      ->set('bundles', array_filter($form_state->getValue('bundles')))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
