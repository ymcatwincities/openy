<?php

namespace Drupal\openy\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form for setting Google Maps API Key during install.
 */
class SetGoogleMapsApiKey extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'openy_set_google_maps_api_key';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, array &$install_state = NULL) {
    $config = \Drupal::service('config.factory')->get('geolocation.settings');

    $form['#title'] = $this->t('Google Maps API Key');

    $form['google_map_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Google Maps API key'),
      '#default_value' => $config->get('google_map_api_key'),
      '#description' => $this->t('For help see the <a href="https://developers.google.com/maps/documentation/javascript/get-api-key#key">Google Maps API documentation</a>.'),
    ];

    $form['actions'] = [
      'continue' => [
        '#type' => 'submit',
        '#value' => $this->t('Continue'),
      ],
      '#type' => 'actions',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::service('config.factory')->getEditable('geolocation.settings');
    $config->set('google_map_api_key', $form_state->getValue('google_map_api_key'));
    $config->save();
  }

}
