<?php

namespace Drupal\openy\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\Exception\ClientException;
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
    // Get Google Tag Manager Account settings container.
    $gtm_config = $config_factory->get('google_tag.settings');
    // Get Optimizely settings container.
    $optimizely_config = $config_factory->get('optimizely.settings');
    // Get Recaptcha settings container.
    $recaptcha_config = $config_factory->get('recaptcha.settings');
    // Get AddThis settings container.
    $addthis_config = $config_factory->get('openy_addthis.settings');
    // Get Lndr settings container.
    $lndr_config = $config_factory->get('lndr.settings');


    $form['#title'] = $this->t('3rd Party Services');

    // TODO: output the fields only if the modules are installed.

    // Google Maps API key.
    if (\Drupal::moduleHandler()->moduleExists('geolocation')) {
      $form['google_map_api_key'] = [
        '#type' => 'textfield',
        '#placeholder' => 'AIzaSyDIFPdDmDsBOFFZcmavCaAqHa3VNKkXLJc',
        '#title' => $this->t('Google Maps API key'),
        '#default_value' => $geo_loc_config->get('google_map_api_key'),
        '#description' => $this->t('Google Maps requires users to use a valid API key. Using the <a href="https://console.developers.google.com/apis" target="_blank">Google API Manager</a>, you can enable the <em>Google Maps JavaScript API</em>. That will create (or reuse) a <em>Browser key</em> which you can paste here.'),
      ];
    }

    // Google Analytics Account ID.
    if (\Drupal::moduleHandler()->moduleExists('google_analytics')) {
      $form['google_analytics_account'] = [
        '#default_value' => $ga_config->get('account'),
        '#description' => $this->t('This ID is unique to each site you want to track separately, and is in the form of UA-xxxxxxx-yy. To get a Web Property ID, <a href=":analytics">register your site with Google Analytics</a>, or if you already have registered your site, go to your Google Analytics Settings page to see the ID next to every site profile. <a href=":webpropertyid">Find more information in the documentation</a>.', [':analytics' => 'http://www.google.com/analytics/',
          ':webpropertyid' => Url::fromUri('https://developers.google.com/analytics/resources/concepts/gaConceptsAccounts', ['fragment' => 'webProperty'])
            ->toString()
        ]),
        '#maxlength' => 20,
        '#placeholder' => 'UA-',
        '#title' => $this->t('Google Analytics Web Property ID'),
        '#type' => 'textfield',
      ];
    }

    // Google Tag Manager Account ID.
    if (\Drupal::moduleHandler()->moduleExists('google_tag')) {
      $form['google_tag_manager_id'] = [
        '#title' => $this->t('Google Tag Manager Container ID'),
        '#description' => $this->t('The ID assigned by Google Tag Manager (GTM) for this website container. To get a container ID, <a href="https://tagmanager.google.com/">sign up for GTM</a> and create a container for your website.'),
        '#default_value' => $gtm_config->get('container_id'),
        '#attributes' => ['placeholder' => ['GTM-xxxxxx']],
        '#maxlength' => 20,
        '#type' => 'textfield',
      ];
    }

    // Optimizely ID Number.
    if (\Drupal::moduleHandler()->moduleExists('optimizely')) {
      $form['optimizely_id'] = [
        '#type' => 'textfield',
        '#placeholder' => 'xxxxxxxxxx',
        '#title' => $this->t('Optimizely ID Number'),
        '#default_value' => $optimizely_config->get('optimizely_id'),
        '#description' => $this->t('Your Optimizely account ID.</br>In order to use this module, you\'ll need an <a href="http://optimize.ly/OZRdc0" target="_blank">Optimizely account</a>. </br>See <a href="https://github.com/ymcatwincities/openy/blob/8.x-1.x/docs/Development/Optimizely.md" target="_blank">Open Y documentation</a> for Optimizely.'),
      ];
    }

    // Recaptcha keys.
    if (\Drupal::moduleHandler()->moduleExists('recaptcha')) {
      $form['recaptcha'] = [
        '#type' => 'details',
        '#title' => $this->t('Recaptcha Settings'),
        '#open' => TRUE,
      ];

      $form['recaptcha']['markup'] = [
        '#type' => 'markup',
        '#markup' => $this->t('<p>If you would like to use the <a href=":url" target="_blank">reCAPTCHA service</a> to reduce potential form spam, create an account and use the provided credentials below.</p>', [':url' => 'http://www.google.com/recaptcha']),
      ];

      $form['recaptcha']['recaptcha_site_key'] = [
        '#default_value' => $recaptcha_config->get('site_key'),
        '#description' => $this->t('The site key given to you when you <a href=":url" target="_blank">register for reCAPTCHA</a>.', [':url' => 'http://www.google.com/recaptcha/admin']),
        '#placeholder' => '6LeQMCcUAAAAAL48HBex3MbH8UTazAH4Vr7cAHEz',
        '#maxlength' => 40,
        '#title' => $this->t('Site key'),
        '#type' => 'textfield',
      ];

      $form['recaptcha']['recaptcha_secret_key'] = [
        '#default_value' => $recaptcha_config->get('secret_key'),
        '#description' => $this->t('The secret key given to you when you <a href=":url" target="_blank">register for reCAPTCHA</a>.', [':url' => 'http://www.google.com/recaptcha/admin']),
        '#maxlength' => 40,
        '#placeholder' => '6LeQMCcUAAAAAPHA6nB1Z0GLpPV8DqrIHzzaSEe6',
        '#title' => $this->t('Secret key'),
        '#type' => 'textfield',
      ];
    }

    // AddThis ID.
    if (\Drupal::moduleHandler()->moduleExists('openy_addthis')) {
      $form['addthis']['public_id'] = [
        '#type' => 'textfield',
        '#title' => $this->t('AddThis public id'),
        '#default_value' => $addthis_config->get('public_id'),
        '#placeholder' => 'ra-xxxxxxxxxxxxxxx',
        '#description' => $this->t('Your AddThis public id. Example: 
        ra-xxxxxxxxxxxxxxx. Currently we support only inline type.'),
      ];
    }

    // Lndr API key.
    if (\Drupal::moduleHandler()->moduleExists('lndr')) {
      $form['lndr'] = [
        '#type' => 'details',
        '#title' => $this->t('Lndr landing page builder'),
        '#open' => TRUE,
      ];

      $form['lndr']['markup'] = [
        '#type' => 'markup',
        '#markup' => $this->t('<p>Lndr is a simple landing page builder that let you create campaigns, events and other landing pages. To learn more, please visit <a href=":url" target="_blank">http://www.lndr.co/</a></p>', [':url' => 'http://www.lndr.co']),
      ];

      $form['lndr']['lndr_api_token'] = [
        '#default_value' => $lndr_config->get('lndr_token'),
        '#description' => $this->t('You can find your API token under your <a href=":analytics">user profile</a>.', [':analytics' => 'https://www.lndr.co/users/edit']),
        '#placeholder' => '',
        '#title' => $this->t('Lndr API key'),
        '#type' => 'textfield',
      ];
    }

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
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    if (!empty(trim($form_state->getValue('google_analytics_account')))) {
      // Trim some text values.
      $form_state->setValue('google_analytics_account', trim($form_state->getValue('google_analytics_account')));

      // Replace all type of dashes (n-dash, m-dash, minus) with normal dashes.
      $form_state->setValue('google_analytics_account', str_replace([
        '–',
        '—',
        '−'
      ], '-', $form_state->getValue('google_analytics_account')));

      if (!preg_match('/^UA-\d+-\d+$/', $form_state->getValue('google_analytics_account'))) {
        $form_state->setErrorByName('google_analytics_account', t('A valid Google Analytics Web Property ID is case sensitive and formatted like UA-xxxxxxx-yy.'));
      }
    }

    if (!empty(trim($form_state->getValue('google_tag_manager_id')))) {
      // Trim the text values.
      $container_id = trim($form_state->getValue('google_tag_manager_id'));

      // Replace all types of dashes (n-dash, m-dash, minus) with a normal dash.
      $container_id = str_replace(['–', '—', '−'], '-', $container_id);
      $form_state->setValue('google_tag_manager_id', $container_id);

      if (!preg_match('/^GTM-\w{4,}$/', $container_id)) {
        // @todo Is there a more specific regular expression that applies?
        // @todo Is there a way to "test the connection" to determine a valid ID for
        // a container? It may be valid but not the correct one for the website.
        $form_state->setError($form['google_tag_manager_id'], $this->t('A valid container ID is case sensitive and formatted like GTM-xxxxxx.'));
      }
    }

    if (!empty(trim($form_state->getValue('recaptcha_site_key'))) || !empty(trim($form_state->getValue('recaptcha_secret_key')))) {
      $recaptcha_site_key = trim($form_state->getValue('recaptcha_site_key'));
      $recaptcha_secret_key = trim($form_state->getValue('recaptcha_secret_key'));
      if (!empty($recaptcha_site_key) && empty($recaptcha_secret_key)) {
        //Site key is populated, secret key is not.
        $form_state->setErrorByName('recaptcha_secret_key', t('A Secret Key must be provided if a Site Key has been entered.'));
      }
      if (!empty($recaptcha_secret_key) && empty($recaptcha_site_key)) {
        //Site key is not populated, secret key is.
        $form_state->setErrorByName('recaptcha_site_key', t('A Site Key must be provided if a Secret Key has been entered.'));
      }
    }

    if (!empty(trim($form_state->getValue('lndr_api_token')))) {
      // Check Lndr API token to see if it is valid.
      try {
        $response = \Drupal::httpClient()->request('POST', 'https://www.lndr.co/v1/validate_token', [
          'form_params' => [
            'token' => $form_state->getValue('lndr_api_token'),
          ]]);

        // @todo: token validation is successful. Let's store this in config.
      }
      catch(ClientException $e) {
        // "You have entered an invalid API token, please copy and paste the API token from your profile in Lndr"
        $form_state->setErrorByName('lndr_api_token', $e->getMessage());
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config_factory = \Drupal::service('config.factory');
    // Save Google Maps API Key.
    if (\Drupal::moduleHandler()->moduleExists('geolocation')) {
      $geo_loc_config = $config_factory->getEditable('geolocation.settings');
      $geo_loc_config->set('google_map_api_key', $form_state->getValue('google_map_api_key'));
      $geo_loc_config->save();
    }

    // Set Google Tag Manager Container ID & create snippets.
    if (!empty($form_state->getValue('google_tag_manager_id'))) {
      $gtm_config = $config_factory->getEditable('google_tag.settings');
      $gtm_config->set('container_id', $form_state->getValue('google_tag_manager_id'));
      $gtm_config->save();
      $this->saveSnippets();
    }

    // Set Google Analytics Account TODO: Add other values?
    if (!empty($form_state->getValue('google_analytics_account'))) {
      $ga_config = $config_factory->getEditable('google_analytics.settings');
      $ga_config->set('account', $form_state->getValue('google_analytics_account'));
      $ga_config->save();
    }

    if (\Drupal::moduleHandler()->moduleExists('optimizely')) {
      $optimizely_config = $config_factory->getEditable('optimizely.settings');
      $optimizely_id = $form_state->getValue('optimizely_id');
      $optimizely_config->set('optimizely_id', $optimizely_id);
      $optimizely_config->save();
      // Update the default project / experiment entry with the account ID value.
      Database::getConnection('default')->update('optimizely')
        ->fields([
          'project_code' => $optimizely_id,
        ])
        ->condition('oid', '1')
        ->execute();
    }

    // Set Recaptcha settings if provided.
    if (!empty($form_state->getValue('recaptcha_site_key'))) {
      $recaptcha_config = $config_factory->getEditable('recaptcha.settings');
      $recaptcha_config
        ->set('site_key', $form_state->getValue('recaptcha_site_key'))
        ->set('secret_key', $form_state->getValue('recaptcha_secret_key'))
        ->save();

      //Set default captcha config to use reCaptcha.
      $captcha_config = $config_factory->getEditable('captcha.settings');
      $captcha_config->set('default_validation', 'recaptcha/reCAPTCHA')
        ->save();
    } else {
      //Set default captcha config to use image captcha.
      $captcha_config = $config_factory->getEditable('captcha.settings');
      $captcha_config->set('default_validation', 'image_captcha/Image')
        ->save();
    }

    // Set Lndr API token.
    if (!empty($form_state->getValue('lndr_api_token'))) {
      $lndr_config = $config_factory->getEditable('lndr.settings');
      $lndr_config->set('lndr_token', $form_state->getValue('lndr_api_token'));
      $lndr_config->save();
    }

    // Set AddThis ID.
    if (\Drupal::moduleHandler()->moduleExists('openy_addthis')) {
      $addthis_config = $config_factory->getEditable('openy_addthis.settings');
      $addthis_public_id = $form_state->getValue('public_id');
      $addthis_config->set('public_id', $addthis_public_id);
      $addthis_config->save();
    }
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
