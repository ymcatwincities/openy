<?php

namespace Drupal\lndr\Form;

use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\HttpFoundation\Request;
use GuzzleHttp\Exception\ClientException;
use Drupal\Core\Form\FormStateInterface;


/**
 * Defines a form that configures devel settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'lndr_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'lndr.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $lndr_config = $this->config('lndr.settings');

    $form['lndr_token'] = [
      '#type' => 'textfield',
      '#title' => t('API token'),
      '#default_value' => $lndr_config->get('lndr_token'),
      '#description' => $this->t('Your Lndr account API token, you can find this information under your user profile in Lndr'),
      '#required' => TRUE,
    ];

    $form['lndr_debug_mode'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Developer mode'),
      '#description' => $this->t('Turn this on to test Lndr API data from local source instead of executing real API call.'),
      '#default_value' => $lndr_config->get('lndr_debug_mode'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    try {
      $response = \Drupal::httpClient()->post(LNDR_API_VALIDATE_TOKEN, [
        'form_params' => [
          'token' => $form_state->getValue('lndr_token'),
        ],
        'headers' => [
          'Content-type' => 'application/x-www-form-urlencoded',
        ]
      ]);

      // @todo: token validation is successful. Let's store this in config

    }
    catch(ClientException $e) {
      // "You have entered an invalid API token, please copy and paste the API token from your profile in Lndr"
      $form_state->setErrorByName('lndr_token', $e->getMessage());
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('lndr.settings')
      ->set('lndr_token', $values['lndr_token'])
      ->set('lndr_debug_mode', $values['lndr_debug_mode'])
      ->save();

    parent::submitForm($form, $form_state);
  }
}
