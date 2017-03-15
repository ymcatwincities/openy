<?php

namespace Drupal\openy\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;
use Drupal\Core\Url;

/**
 * Defines a form for setting Google Maps API Key during install.
 */
class ThirdPartyServicesForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'openy_third_party_services';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, array &$install_state = NULL) {
    // Get Config factory.
    $config_factory = \Drupal::service('config.factory');
    // Get Google Maps API settings container.
    $geo_loc_config = $config_factory->get('geolocation.settings');
    // Get Google Analytics Account settings container.
    $ga_config = $config_factory->get('google_analytics.settings');
    // Get Optimizely settings container.
    $optimizely_config = $config_factory->get('optimizely.settings');

    $form['#title'] = $this->t('3rd Party Services');

    // Google Maps API key.
    $form['google_map_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Google Maps API key'),
      '#default_value' => $geo_loc_config->get('google_map_api_key'),
      '#description' => $this->t('Google Maps requires users to use a valid API key. Using the <a href="https://console.developers.google.com/apis" target="_blank">Google API Manager</a>, you can enable the <em>Google Maps JavaScript API</em>. That will create (or reuse) a <em>Browser key</em> which you can paste here.'),
    ];

    // Google Analytics Account ID.
    $form['google_analytics_account'] = [
      '#default_value' => $ga_config->get('account'),
      '#description' => $this->t('This ID is unique to each site you want to track separately, and is in the form of UA-xxxxxxx-yy. To get a Web Property ID, <a href=":analytics">register your site with Google Analytics</a>, or if you already have registered your site, go to your Google Analytics Settings page to see the ID next to every site profile. <a href=":webpropertyid">Find more information in the documentation</a>.', [':analytics' => 'http://www.google.com/analytics/', ':webpropertyid' => Url::fromUri('https://developers.google.com/analytics/resources/concepts/gaConceptsAccounts', ['fragment' => 'webProperty'])->toString()]),
      '#maxlength' => 20,
      '#placeholder' => 'UA-',
      '#title' => $this->t('Google Analytics Web Property ID'),
      '#type' => 'textfield',
    ];

    // Optimizely ID Number.
    $form['optimizely_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Optimizely ID Number'),
      '#default_value' => $optimizely_config->get('optimizely_id'),
      '#description' => $this->t('Your Optimizely account ID.</br>In order to use this module, you\'ll need an <a href="http://optimize.ly/OZRdc0" target="_blank">Optimizely account</a>. </br>See <a href="https://github.com/ymcatwincities/openy/blob/8.x-1.x/docs/Development/Optimizely.md" target="_blank">Open Y documentation</a> for Optimizely.'),
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
    // Save Google Maps API Key.
    $config_factory = \Drupal::service('config.factory');
    $geo_loc_config = $config_factory->getEditable('geolocation.settings');
    $optimizely_config = $config_factory->getEditable('optimizely.settings');

    $geo_loc_config->set('google_map_api_key', $form_state->getValue('google_map_api_key'));
    $geo_loc_config->save();

    // Set Google Analytics Account TODO: Add other values?
    if (!empty($form_state->getValue('google_analytics_account'))) {
      $ga_config = $config_factory->getEditable('google_analytics.settings');
      $ga_config->set('account', $form_state->getValue('google_analytics_account'));
      $ga_config->save();
    }

    $optimizely_id = $form_state->getValue('optimizely_id');
    $optimizely_config->set('optimizely_id', $optimizely_id);
    $optimizely_config->save();
    // Update the default project / experiment entry with the account ID value.
    Database::getConnection('default')->update('optimizely')
      ->fields(array(
        'project_code' => $optimizely_id,
      ))
      ->condition('oid', '1')
      ->execute();
  }

}
