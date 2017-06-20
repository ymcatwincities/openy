<?php

namespace Drupal\geolocation\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\geolocation\GoogleMapsDisplayTrait;

/**
 * Implements the GeolocationGoogleMapAPIkey form controller.
 *
 * @see \Drupal\Core\Form\FormBase
 */
class GeolocationSettings extends ConfigFormBase {

  use GoogleMapsDisplayTrait;

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory->get('geolocation.settings');

    $form['#tree'] = TRUE;

    $form['google_map_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Google Maps API key'),
      '#default_value' => $config->get('google_map_api_key'),
      '#description' => $this->t('Google requires users to use a valid API key. Using the <a href="https://console.developers.google.com/apis">Google API Manager</a>, you can enable the <em>Google Maps JavaScript API</em>. That will create (or reuse) a <em>Browser key</em> which you can paste here.'),
    ];

    $custom_parameters = $config->get('google_map_custom_url_parameters');
    $form['parameters'] = [
      '#type' => 'details',
      '#title' => $this->t('Optional Google Parameters'),
      '#description' => $this->t('None of these parameters is required. Please note: modules might extend or override these options.'),
      '#open' => !empty($custom_parameters),
    ];

    $form['parameters']['libraries'] = [
      '#type' => 'fieldset',
      '#title' => $this->t("Google Maps Libraries - 'libraries'"),
      '#description' => $this->t('See <a href=":google_libraries_link">Google libraries documentation</a>.', [':google_libraries_link' => 'https://developers.google.com/maps/documentation/javascript/libraries']),
      '#attributes' => [
        'id' => 'geolocation-google-libraries',
      ],
    ];

    $module_parameters = \Drupal::moduleHandler()->invokeAll('geolocation_google_maps_parameters');

    if (!empty($module_parameters['libraries'])) {
      $module_libraries = array_unique($module_parameters['libraries']);
      $form['parameters']['libraries']['module_defined'] = [
        '#prefix' => $this->t('Module defined library requirements - These libraries will be loaded anyway and should not be listed here.'),
        '#theme' => 'item_list',
        '#items' => $module_libraries,
      ];
    }

    $default_libraries = empty($custom_parameters['libraries']) ? [] : $custom_parameters['libraries'];
    $max = max($form_state->get('fields_count'), count($default_libraries), 0);
    $form_state->set('fields_count', $max);

    // Add elements that don't already exist.
    for ($delta = 0; $delta <= $max; $delta++) {
      if (empty($form['parameters']['libraries'][$delta])) {
        $form['parameters']['libraries'][$delta] = [
          '#type' => 'textfield',
          '#title' => $this->t('Library name'),
          '#default_value' => empty($default_libraries[$delta]) ? '' : $default_libraries[$delta],
        ];
      }
    }

    $form['parameters']['libraries']['add'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add library'),
      '#submit' => [[$this, 'addLibrariesSubmit']],
      '#ajax' => [
        'callback' => [$this, 'addLibrariesCallback'],
        'wrapper' => 'geolocation-google-libraries',
        'effect' => 'fade',
      ],
    ];

    $form['parameters']['region'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Google Maps Region - 'region'"),
      '#default_value' => empty($custom_parameters['region']) ?: $custom_parameters['region'],
    ];
    $form['parameters']['language'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Google Maps Localization - 'language'"),
      '#default_value' => empty($custom_parameters['language']) ?: $custom_parameters['language'],
      '#description' => $this->t('See <a href=":google_localization_link">Google Maps API - Localizing the Map</a>.', [':google_localization_link' => 'https://developers.google.com/maps/documentation/javascript/localization']),
    ];

    $form['parameters']['v'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Google Maps Version - 'v'"),
      '#default_value' => empty($custom_parameters['v']) ?: $custom_parameters['v'],
      '#description' => $this->t('Will default to current experimental. See <a href=":google_version_link">Google Maps API - Versioning</a>.', [':google_version_link' => 'https://developers.google.com/maps/documentation/javascript/versions']),
    ];

    $form['parameters']['client'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Google Maps Client ID - 'client'"),
      '#default_value' => empty($custom_parameters['client']) ?: $custom_parameters['client'],
      '#description' => $this->t('Attention: setting this option has major usage implications. See <a href=":google_client_id_link">Google Maps Authentication documentation</a>.', [':google_client_id_link' => 'https://developers.google.com/maps/documentation/javascript/get-api-key#client-id']),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Ajax submit to add new field.
   */
  public function addLibrariesSubmit(array &$form, FormStateInterface &$form_state) {
    $max = $form_state->get('fields_count') + 1;
    $form_state->set('fields_count', $max);
    $form_state->setRebuild(TRUE);
  }

  /**
   * Ajax callback to add new field.
   */
  public function addLibrariesCallback(array &$form, FormStateInterface &$form_state) {
    return $form['parameters']['libraries'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'geolocation_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'geolocation.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Config\Config $config */
    $config = \Drupal::service('config.factory')->getEditable('geolocation.settings');
    $config->set('google_map_api_key', $form_state->getValue('google_map_api_key'));

    $parameters = $form_state->getValue('parameters');
    unset($parameters['libraries']['add']);
    $parameters['libraries'] = array_unique($parameters['libraries']);
    foreach ($parameters['libraries'] as $key => $library) {
      if (empty($library)) {
        unset($parameters['libraries'][$key]);
      }
    }
    $parameters['libraries'] = array_values($parameters['libraries']);
    $config->set('google_map_custom_url_parameters', $parameters);

    $config->save();
  }

}
