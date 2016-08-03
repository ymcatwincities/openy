<?php

namespace Drupal\ymca_retention\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ymca_mappings\Entity\Mapping;
use Drupal\ymca_mappings\LocationMappingRepository;

/**
 * Provides form for managing module settings.
 */
class SettingsBranchesForm extends ConfigFormBase {

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

    /** @var LocationMappingRepository $repo */
    $repo = \Drupal::service('ymca_mappings.location_repository');
    $locations = $repo->loadAll();

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
