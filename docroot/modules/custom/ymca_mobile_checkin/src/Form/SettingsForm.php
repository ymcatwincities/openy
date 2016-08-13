<?php

namespace Drupal\ymca_mobile_checkin\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides form for managing module settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ymca_mobile_checkin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['ymca_mobile_checkin.shield_config'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('ymca_mobile_checkin.shield_config');

    $form['shield'] = [
      '#type' => 'fieldset',
      '#title' => 'HTTP Authorization',
    ];
    $form['shield']['enabled'] = [
      '#type' => 'checkbox',
      '#title' => 'enabled',
      '#default_value' => $config->get('enabled'),
    ];
    $form['shield']['login'] = [
      '#type' => 'textfield',
      '#title' => 'login',
      '#default_value' => $config->get('login'),
    ];
    $form['shield']['password'] = [
      '#type' => 'textfield',
      '#title' => 'password',
      '#default_value' => $config->get('password'),
    ];
    $form['shield']['message'] = [
      '#type' => 'textfield',
      '#title' => 'message',
      '#default_value' => $config->get('message'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('ymca_mobile_checkin.shield_config')
      ->set('enabled', $form_state->getValue('enabled'))
      ->set('login', $form_state->getValue('login'))
      ->set('password', $form_state->getValue('password'))
      ->set('message', $form_state->getValue('message'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
