<?php

namespace Drupal\openy_google_search\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Settings Form for openy_google_search.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'openy_google_search_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'openy_google_search.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('openy_google_search.settings');

    $form['google_engine_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Google Search Engine ID'),
      '#size' => 40,
      '#default_value' => !empty($config->get('google_engine_id')) ? $config->get('google_engine_id') : '',
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $config = $this->config('openy_google_search.settings');
    $config->set('google_engine_id', $values['google_engine_id']);
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
