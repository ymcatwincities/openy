<?php

namespace Drupal\openy\Form;

use Drupal\Core\Asset\JsCollectionOptimizer;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a form for setting Third party services parameters during install.
 */
class ThirdPartyServicesForm extends FormBase {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The Collection Optimizer.
   *
   * @var \Drupal\Core\Asset\JsCollectionOptimizer
   */
  protected $collectionOptimizer;

  /**
   * Constructs a new ThirdPartyServicesForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Asset\JsCollectionOptimizer $collection_optimizer
   *   The JS Collection Optimizer.
   */
  public function __construct(ConfigFactoryInterface $config_factory, JsCollectionOptimizer $collection_optimizer) {
    $this->configFactory = $config_factory;
    $this->collectionOptimizer = $collection_optimizer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('asset.js.collection_optimizer')
    );
  }

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

    $form['#title'] = $this->t('3rd Party Services');

    // Output the fields only if the modules are installed.
    // Google Maps API key.
    if (\Drupal::moduleHandler()->moduleExists('geolocation')) {
      // Get Google Maps API settings container.
      $geo_loc_config = $this->configFactory->get('geolocation_google_maps.settings');
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
      // Get Google Analytics Account settings container.
      $ga_config = $this->configFactory->get('google_analytics.settings');
      $form['google_analytics_account'] = [
        '#default_value' => $ga_config->get('account'),
        '#description' => $this->t('This ID is unique to each site you want to track separately, and is in the form of UA-xxxxxxx-yy. To get a Web Property ID, <a href=":analytics">register your site with Google Analytics</a>, or if you already have registered your site, go to your Google Analytics Settings page to see the ID next to every site profile. <a href=":webpropertyid">Find more information in the documentation</a>.',
          [
            ':analytics' => 'http://www.google.com/analytics/',
            ':webpropertyid' => Url::fromUri('https://developers.google.com/analytics/resources/concepts/gaConceptsAccounts', ['fragment' => 'webProperty'])->toString(),
          ]),
        '#maxlength' => 20,
        '#placeholder' => 'UA-',
        '#title' => $this->t('Google Analytics Web Property ID'),
        '#type' => 'textfield',
      ];
    }

    // Google Tag Manager Account ID.
    if (\Drupal::moduleHandler()->moduleExists('google_tag')) {
      // Get Google Tag Manager Account settings container.
      $gtm_config = $this->configFactory->get('google_tag.settings');
      $form['google_tag_manager_id'] = [
        '#title' => $this->t('Google Tag Manager Container ID'),
        '#description' => $this->t('The ID assigned by Google Tag Manager (GTM) for this website container. To get a container ID, <a href="https://tagmanager.google.com/">sign up for GTM</a> and create a container for your website.'),
        '#default_value' => $gtm_config->get('container_id'),
        '#attributes' => ['placeholder' => ['GTM-xxxxxx']],
        '#maxlength' => 20,
        '#type' => 'textfield',
      ];
    }

    // Recaptcha keys.
    if (\Drupal::moduleHandler()->moduleExists('recaptcha')) {
      // Get Recaptcha settings container.
      $recaptcha_config = $this->configFactory->get('recaptcha.settings');
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
      // Get AddThis settings container.
      $addthis_config = $this->configFactory->get('openy_addthis.settings');
      $form['addthis']['public_id'] = [
        '#type' => 'textfield',
        '#title' => $this->t('AddThis public id'),
        '#default_value' => $addthis_config->get('public_id'),
        '#placeholder' => 'ra-xxxxxxxxxxxxxxx',
        '#description' => $this->t('Your AddThis public id. Example:
        ra-xxxxxxxxxxxxxxx. Currently we support only inline type.'),
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
        '−',
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
        // Site key is populated, secret key is not.
        $form_state->setErrorByName('recaptcha_secret_key', t('A Secret Key must be provided if a Site Key has been entered.'));
      }
      if (!empty($recaptcha_secret_key) && empty($recaptcha_site_key)) {
        // Site key is not populated, secret key is.
        $form_state->setErrorByName('recaptcha_site_key', t('A Site Key must be provided if a Secret Key has been entered.'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Save Google Maps API Key.
    if (\Drupal::moduleHandler()->moduleExists('geolocation')) {
      $geo_loc_config = $this->configFactory->getEditable('geolocation_google_maps.settings');
      $geo_loc_config->set('google_map_api_key', $form_state->getValue('google_map_api_key'));
      $geo_loc_config->save();
    }

    // Set Google Tag Manager Container ID & create snippets.
    if (!empty($form_state->getValue('google_tag_manager_id'))) {
      $gtm_config = $this->configFactory->getEditable('google_tag.settings');
      $gtm_config->set('container_id', $form_state->getValue('google_tag_manager_id'));
      $gtm_config->save();
      $this->saveSnippets();
    }

    // Set Google Analytics Account TODO: Add other values?
    if (!empty($form_state->getValue('google_analytics_account'))) {
      $ga_config = $this->configFactory->getEditable('google_analytics.settings');
      $ga_config->set('account', $form_state->getValue('google_analytics_account'));
      $ga_config->save();
    }

    // Set Recaptcha settings if provided.
    if (!empty($form_state->getValue('recaptcha_site_key'))) {
      $recaptcha_config = $this->configFactory->getEditable('recaptcha.settings');
      $recaptcha_config
        ->set('site_key', $form_state->getValue('recaptcha_site_key'))
        ->set('secret_key', $form_state->getValue('recaptcha_secret_key'))
        ->save();

      // Set default captcha config to use reCaptcha.
      $captcha_config = $this->configFactory->getEditable('captcha.settings');
      $captcha_config->set('default_validation', 'recaptcha/reCAPTCHA')
        ->save();
    }
    else {
      // Set default captcha config to use image captcha.
      $captcha_config = $this->configFactory->getEditable('captcha.settings');
      $captcha_config->set('default_validation', 'image_captcha/Image')
        ->save();
    }

    // Set AddThis ID.
    if (\Drupal::moduleHandler()->moduleExists('openy_addthis')) {
      $addthis_config = $this->configFactory->getEditable('openy_addthis.settings');
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
      $this->collectionOptimizer->deleteAll();
      _drupal_flush_css_js();
    }
    return TRUE;
  }

}
