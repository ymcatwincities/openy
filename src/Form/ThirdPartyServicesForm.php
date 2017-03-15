<?php

namespace Drupal\openy\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;

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
    // Get Google Tag Manager Account settings container.
    $gtm_config = $config_factory->get('google_tag.settings');
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

    // Google Tag Manager Account ID.
    $form['google_tag_manager_id'] = [
      '#title' => $this->t('Container ID'),
      '#description' => $this->t('The ID assigned by Google Tag Manager (GTM) for this website container. To get a container ID, <a href="https://tagmanager.google.com/">sign up for GTM</a> and create a container for your website.'),
      '#default_value' => $gtm_config->get('container_id'),
      '#attributes' => ['placeholder' => ['GTM-xxxxxx']],
      '#maxlength' => 20,
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

    // Set Google Tag Manager Container ID & create snippets.
    if (!empty($form_state->getValue('google_tag_manager_id'))) {
      $gtm_config = $config_factory->getEditable('google_tag.settings');
      $gtm_config->set('container_id', $form_state->getValue('google_tag_manager_id'));
      $gtm_config->save();
      $this->saveSnippets();
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

  /**
   * Saves JS snippet files based on current settings.
   *
   * @return bool
   *   Whether the files were saved.
   */
  public function saveSnippets() {
    // Save the altered snippets after hook_google_tag_snippets_alter().
    module_load_include('inc', 'google_tag', 'includes/snippet');
    $result = TRUE;
    $snippets = google_tag_snippets();
    foreach ($snippets as $type => $snippet) {
      $path = file_unmanaged_save_data($snippet, "public://js/google_tag.$type.js", FILE_EXISTS_REPLACE);
      $result = !$path ? FALSE : $result;
    }
    if (!$result) {
      drupal_set_message(t('An error occurred saving one or more snippet files. Please try again or contact the site administrator if it persists.'));
    }
    else {
      drupal_set_message(t('Created three snippet files based on configuration.'));
      \Drupal::service('asset.js.collection_optimizer')->deleteAll();
      _drupal_flush_css_js();
    }
    return TRUE;
  }

}
