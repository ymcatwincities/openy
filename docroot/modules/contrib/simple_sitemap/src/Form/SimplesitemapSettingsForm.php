<?php

namespace Drupal\simple_sitemap\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\UrlHelper;

/**
 * Class SimplesitemapSettingsForm.
 *
 * @package Drupal\simple_sitemap\Form
 */
class SimplesitemapSettingsForm extends SimplesitemapFormBase {

  private $formSettings = [
    'max_links',
    'cron_generate',
    'remove_duplicates',
    'skip_untranslated',
    'batch_process_limit',
    'base_url',
  ];

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'simple_sitemap_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['simple_sitemap_settings']['#prefix'] = $this->getDonationText();

    $form['simple_sitemap_settings']['regenerate'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Regenerate sitemap'),
      '#markup' => '<p>' . $this->t('This will regenerate the XML sitemap for all languages.') . '</p>',
    ];

    $form['simple_sitemap_settings']['regenerate']['regenerate_submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Regenerate sitemap'),
      '#submit' => ['::generateSitemap'],
    // Skip form-level validator.
      '#validate' => [],
    ];

    $form['simple_sitemap_settings']['settings'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Settings'),
    ];

    $form['simple_sitemap_settings']['settings']['cron_generate'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Regenerate the sitemap on every cron run'),
      '#description' => $this->t('Uncheck this if you intend to only regenerate the sitemap manually or via drush.'),
      '#default_value' => $this->generator->getSetting('cron_generate', TRUE),
    ];

    $form['simple_sitemap_settings']['advanced'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced settings'),
      '#open' => TRUE,
    ];

    $form['simple_sitemap_settings']['advanced']['base_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Default base URL'),
      '#default_value' => $this->generator->getSetting('base_url', ''),
      '#size' => 30,
      '#description' => $this->t('On some hosting providers it is impossible to pass parameters to cron to tell Drupal which URL to bootstrap with. In this case the base URL of sitemap links can be set here.'),
    ];

    $form['simple_sitemap_settings']['advanced']['remove_duplicates'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Exclude duplicate links'),
      '#description' => $this->t('Uncheck this to significantly speed up the sitemap generation process on a huge site (more than 20 000 indexed entities).'),
      '#default_value' => $this->generator->getSetting('remove_duplicates', TRUE),
    ];

    $form['simple_sitemap_settings']['advanced']['skip_untranslated'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Skip non-existent translations'),
      '#description' => $this->t('If unchecked, entity links are generated for every language installed on the site, regardless whether the entity has been translated to a language or not.<br/>If checked, entity links are generated exclusively for languages the entity has been translated to. This setting has no effect on non-entity paths like homepage.'),
      '#default_value' => $this->generator->getSetting('skip_untranslated', FALSE),
    ];

    $form['simple_sitemap_settings']['advanced']['max_links'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum links in a sitemap'),
      '#min' => 1,
      '#description' => $this->t("The maximum number of links one sitemap can hold. If more links are generated than set here, a sitemap index will be created and the links split into several sub-sitemaps.<br/>50 000 links is the maximum Google will parse per sitemap, however it is advisable to set this to a lower number. If left blank, all links will be shown on a single sitemap."),
      '#default_value' => $this->generator->getSetting('max_links', 2000),
    ];

    $form['simple_sitemap_settings']['advanced']['batch_process_limit'] = [
      '#type' => 'number',
      '#title' => $this->t('Refresh batch every n links'),
      '#min' => 1,
      '#description' => $this->t("During sitemap generation, the batch process will issue a page refresh after n links processed to prevent PHP timeouts and memory exhaustion.<br/>Increasing this number will reduce the number of times Drupal has to bootstrap (thus speeding up the generation process), but will require more memory and less strict PHP timeout settings."),
      '#default_value' => $this->generator->getSetting('batch_process_limit', 1500),
      '#required' => TRUE,
    ];

    $this->formHelper->displayRegenerateNow($form['simple_sitemap_settings']);

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $base_url = $form_state->getValue('base_url');
    $form_state->setValue('base_url', rtrim($base_url, '/'));
    if ($base_url != '' && !UrlHelper::isValid($base_url, TRUE)) {
      $form_state->setErrorByName('base_url', t('The base URL is invalid.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    foreach ($this->formSettings as $setting_name) {
      $this->generator->saveSetting($setting_name, $form_state->getValue($setting_name));
    }
    parent::submitForm($form, $form_state);

    // Regenerate sitemaps according to user setting.
    if ($form_state->getValue('simple_sitemap_regenerate_now')) {
      $this->generator->generateSitemap();
    }
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  public function generateSitemap(array &$form, FormStateInterface $form_state) {
    $this->generator->generateSitemap();
  }

}
