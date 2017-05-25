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

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('openy_group_schedules.settings')
      ->set('account_id', $values['account_id'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
