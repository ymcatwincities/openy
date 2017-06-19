<?php

namespace Drupal\openy_group_schedules\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;

/**
 * Provides GroupEx settings form.
 */
class GroupexSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'openy_group_schedules_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['openy_group_schedules.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('openy_group_schedules.settings');

    $form['account_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('GroupEx Account ID'),
      '#default_value' => !empty($config->get('account_id')) ? $config->get('account_id') : '',
    ];

    $form['cache_max_age'] = [
      '#type' => 'number',
      '#title' => $this->t('Cache Max Age'),
      '#description' => $this->t('Maximum cache age in seconds.'),
      '#default_value' => !empty($config->get('cache_max_age')) ? $config->get('cache_max_age') : 3600,
    ];

    $form['location_max'] = [
      '#type' => 'number',
      '#title' => $this->t('Location Max Select'),
      '#description' => $this->t('Maximum number of locations that can be selected.'),
      '#default_value' => !empty($config->get('location_max')) ? $config->get('location_max') : 4,
    ];

    $form['days_range'] = [
      '#type' => 'number',
      '#title' => $this->t('Days Range'),
      '#description' => $this->t('Number of days to include in form options.'),
      '#default_value' => !empty($config->get('days_range')) ? $config->get('days_range') : 14,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $config = $this->config('openy_group_schedules.settings');
    $config->set('account_id', $values['account_id']);
    $config->set('cache_max_age', $values['cache_max_age']);
    $config->set('location_max', $values['location_max']);
    $config->set('days_range', $values['days_range']);
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
