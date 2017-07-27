<?php

namespace Drupal\openy_digital_signage_groupex_schedule\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;

/**
 * Provides form for managing module settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'openy_digital_signage_groupex_schedule_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['openy_digital_signage_groupex_schedule.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('openy_digital_signage_groupex_schedule.settings');
    /* @var \Drupal\ymca_mappings\LocationMappingRepository $mapping */
    $mapping = \Drupal::service('ymca_mappings.location_repository');
    $entities = $mapping->loadAllLocationsWithGroupExId();
    $locations = [];
    /* @var \Drupal\ymca_mappings\Entity\Mapping $entity */
    foreach ($entities as $entity) {
      $locations[$entity->id()] = $entity->getName();
    }
    // @todo For Future: add a condition to check is there at least one location.
    $form['locations'] = [
      '#title' => $this->t('Locations to sync'),
      '#type' => 'checkboxes',
      '#options' => $locations,
      '#default_value' => $config->get('locations'),
    ];
    $form['period'] = [
      '#title' => $this->t('Period'),
      '#type' => 'textfield',
      '#description' => $this->t('Specify the number of days in advance to import scheduled sessions.'),
      '#default_value' => $config->get('period'),
    ];
    $uid = 0;
    if ($default_uid = $config->get('default_author')) {
      $uid = $default_uid;
    }
    $form['default_author'] = [
      '#type' => 'entity_autocomplete',
      '#target_type' => 'user',
      '#default_value' => User::load($uid),
      '#selection_settings' => ['include_anonymous' => TRUE],
      '#validate_reference' => FALSE,
      '#maxlength' => 60,
      '#title' => $this->t('Default Author for imported sessions'),
      '#description' => $this->t('Leave blank for %anonymous.', [
        '%anonymous' => $this->config('user.settings')->get('anonymous'),
      ]),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('openy_digital_signage_groupex_schedule.settings')
      ->set('locations', array_filter($form_state->getValue('locations')))
      ->set('period', $form_state->getValue('period'))
      ->set('default_author', $form_state->getValue('default_author'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
