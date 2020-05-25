<?php

namespace Drupal\ymca_sync\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\ymca_sync\SyncRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides ymca_sync settings form.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Available syncers.
   *
   * @var array
   */
  protected $syncers = [];

  /**
   * Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ymca_sync_settings';
  }

  /**
   * SettingsForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\ymca_sync\SyncRepository $syncers
   *   The syncers repository.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(ConfigFactoryInterface $config_factory, SyncRepository $syncers, ModuleHandlerInterface $module_handler) {
    parent::__construct($config_factory);
    $this->syncers = $syncers;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('ymca_sync.sync_repository'),
      $container->get('module_handler')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['ymca_sync.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('ymca_sync.settings');

    $active = $config->get('active_syncers');
    $default_value = [];
    foreach ($this->syncers->getSyncers() as $syncer) {
      if (in_array($syncer, $active)) {
        $default_value[] = $syncer;
      }
    }
    $form['syncers'] = [
      '#title' => $this->t('Active syncers'),
      '#type' => 'checkboxes',
      '#options' => array_combine($this->syncers->getSyncers(), $this->syncers->getSyncers()),
      '#description' => $this->t("If the syncer is selected it's ready to run. Remove selection to disable any syncer."),
      '#default_value' => $default_value,
    ];

    // Allow modules to add descriptions of their syncers.
    $description_items = [];
    $this->moduleHandler->alter('ymca_sync_description', $description_items);

    if (!empty($description_items)) {
      $form['description'] = [
        '#theme' => 'item_list',
        '#items' => $description_items,
        '#title' => $this->t('Syncers description:'),
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $syncers = array_values(array_filter($values['syncers']));

    $this->config('ymca_sync.settings')
      ->set('active_syncers', $syncers)
      ->save();

    parent::submitForm($form, $form_state);
  }

}
