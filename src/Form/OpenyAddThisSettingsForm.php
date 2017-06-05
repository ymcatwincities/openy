<?php

namespace Drupal\openy_addthis\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure AddThis settings for this site.
 */
class OpenyAddThisSettingsForm extends ConfigFormBase {

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
      '#type' => 'textarea',
      '#title' => $this->t('AddThis public id'),
      '#default_value' => $config->get('public_id'),
      '#required' => TRUE,
      '#description' => $this->t('Your AddThis public id.'),
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
      ->save();

    parent::submitForm($form, $form_state);
  }

}
