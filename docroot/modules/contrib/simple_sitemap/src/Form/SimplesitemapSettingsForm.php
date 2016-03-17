<?php

/**
 * @file
 * Contains \Drupal\simple_sitemap\Form\SimplesitemapSettingsForm.
 */

namespace Drupal\simple_sitemap\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\simple_sitemap\Simplesitemap;

/**
 * SimplesitemapSettingsFrom
 */
class SimplesitemapSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'simple_sitemap_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['simple_sitemap.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $sitemap = new Simplesitemap;

    $form['simple_sitemap_settings']['regenerate'] = array(
      '#title' => t('Regenerate sitemap'),
      '#type' => 'fieldset',
      '#markup' => '<p>' . t('This will regenerate the XML sitemap for all languages.') . '</p>',
    );

    $form['simple_sitemap_settings']['regenerate']['regenerate_submit'] = array(
      '#type' => 'submit',
      '#value' => t('Regenerate sitemap'),
      '#submit' => array('::generate_sitemap'),
      '#validate' => array(), // Skip form-level validator.
    );

    $form['simple_sitemap_settings']['settings'] = array(
      '#title' => t('Settings'),
      '#type' => 'fieldset',
//      '#markup' => '<p>' . t('Various sitemap settings.') . '</p>',
    );

    $form['simple_sitemap_settings']['settings']['cron_generate'] = array(
      '#type' => 'checkbox',
      '#title' => t('Regenerate the sitemap on every cron run'),
      '#description' => t('Uncheck this if you intend to only regenerate the sitemap manually or via drush.'),
      '#default_value' => $sitemap->get_setting('cron_generate'),
    );

    $form['simple_sitemap_settings']['advanced'] = array(
      '#title' => t('Advanced settings'),
      '#type' => 'details',
//      '#markup' => '<p>' . t('Various performance settings.') . '</p>',
    );

    $form['simple_sitemap_settings']['advanced']['remove_duplicates'] = array(
      '#type' => 'checkbox',
      '#title' => t('Remove duplicate links'),
      '#description' => t('Uncheck this to significantly speed up the sitemap generation process on a huge site (>20 000 entities).'),
      '#default_value' => $sitemap->get_setting('remove_duplicates'),
    );

    $form['simple_sitemap_settings']['advanced']['max_links'] = array(
      '#title' => t('Maximum links in a sitemap'),
      '#description' => t("The maximum number of links one sitemap can hold. If more links are generated than set here, a sitemap index will be created and the links split into several sub-sitemaps.<br/>50 000 links is the maximum Google will parse per sitemap, however it is advisable to set this to a lower number. If left blank, all links will be shown on a single sitemap."),
      '#type' => 'textfield',
      '#maxlength' => 5,
      '#size' => 5,
      '#default_value' => $sitemap->get_setting('max_links'),
    );

    $form['simple_sitemap_settings']['advanced']['batch_process_limit'] = array(
      '#title' => t('Refresh batch every n links'),
      '#description' => t("During sitemap generation, the batch process will issue a page refresh after n links processed to prevent PHP timeout and memory exhaustion. Increasing this number will reduce the number of times Drupal has to bootstrap (thus speeding up the generation process), but will require more memory and less strict PHP timeout settings."),
      '#type' => 'textfield',
      '#maxlength' => 5,
      '#size' => 5,
      '#default_value' => $sitemap->get_setting('batch_process_limit'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $max_links = $form_state->getValue('max_links');
    if ($max_links != '') {
      if (!is_numeric($max_links) || $max_links < 1 || $max_links != round($max_links)) {
        $form_state->setErrorByName('', t("The value of the max links field must be a positive integer greater than 0."));
      }
    }

  $batch_process_limit = $form_state->getValue('batch_process_limit');
    if (!is_numeric($batch_process_limit) || $batch_process_limit < 1 || $batch_process_limit != round($batch_process_limit)) {
      $form_state->setErrorByName('', t("The value of the batch refresh rate field must be a positive integer greater than 0."));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $sitemap = new Simplesitemap;
    $sitemap->save_setting('max_links', $form_state->getValue('max_links'));
    $sitemap->save_setting('cron_generate', $form_state->getValue('cron_generate'));
    $sitemap->save_setting('remove_duplicates', $form_state->getValue('remove_duplicates'));
    $sitemap->save_setting('batch_process_limit', $form_state->getValue('batch_process_limit'));
    parent::submitForm($form, $form_state);
  }

  public function generate_sitemap(array &$form, FormStateInterface $form_state) {
    $sitemap = new Simplesitemap;
    $sitemap->generate_sitemap();
  }
}
