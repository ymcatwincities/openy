<?php

namespace Drupal\ymca_retention\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ymca_mappings\Entity\Mapping;
use Drupal\ymca_mappings\LocationMappingRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides form for managing module settings.
 */
class SettingsBranchesForm extends ConfigFormBase {

  /**
   * The location mapping repository.
   *
   * @var \Drupal\ymca_mappings\LocationMappingRepository;
   */
  protected $locationRepository;

  /**
   * SettingsBranchesForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\ymca_mappings\LocationMappingRepository $location_repository
   *   The location mapping repository.
   */
  public function __construct(ConfigFactoryInterface $config_factory, LocationMappingRepository $location_repository) {
    parent::__construct($config_factory);
    $this->locationRepository = $location_repository;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('ymca_mappings.location_repository')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ymca_retention_branches_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['ymca_retention.branches_settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('ymca_retention.branches_settings');

    $locations = $this->locationRepository->loadAll();

    $options = [];
    /** @var Mapping $location */
    foreach ($locations as $location) {
      if ($branch_id = $location->get('field_location_personify_brcode')->value) {
        $options[$branch_id] = $location->getName();
      }
    }

    $form['excluded_branches'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Excluded branches/locations'),
      '#description' => $this->t('Check the branches/locations to exclude from leaderboard and prizes.'),
      '#options' => $options,
      '#default_value' => $config->get('excluded_branches'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('ymca_retention.branches_settings')
      ->set('excluded_branches', array_filter($form_state->getValue('excluded_branches')))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
