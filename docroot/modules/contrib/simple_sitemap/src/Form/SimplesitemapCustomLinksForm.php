<?php

/**
 * @file
 * Contains \Drupal\simple_sitemap\Form\SimplesitemapCustomLinksForm.
 */

namespace Drupal\simple_sitemap\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\simple_sitemap\Simplesitemap;

/**
 * SimplesitemapCustomLinksFrom
 */
class SimplesitemapCustomLinksForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'simple_sitemap_custom_links_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['simple_sitemap.settings_custom'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $sitemap = new Simplesitemap;
    $setting_string = '';
    foreach ($sitemap->get_config('custom') as $custom_link) {

      // todo: remove this statement after removing the index key from the configuration.
      if (isset($custom_link['index']) && $custom_link['index'] == 0)
        continue;

      $setting_string .= isset($custom_link['priority']) ? $custom_link['path'] . ' ' . $custom_link['priority'] : $custom_link['path'];
      $setting_string .= "\r\n";
    }

    $form['simple_sitemap_custom'] = array(
      '#title' => t('Custom links'),
      '#type' => 'fieldset',
      '#markup' => '<p>' . t('Add custom internal drupal paths and their priorities to the XML sitemap.') . '</p>',
    );

    $form['simple_sitemap_custom']['custom_links'] = array(
      '#type' => 'textarea',
      '#title' => t('Relative Drupal paths'),
      '#default_value' => $setting_string,
      '#description' => t("Please specify drupal internal (relative) paths, one per line. Do not forget to prepend the paths with a '/'. You can optionally add a priority (0.0 - 1.0) by appending it to the path after a space. The home page with the highest priority would be <em>/ 1</em>, the contact page with a medium priority would be <em>/contact 0.5</em>."),
    );

    $form['simple_sitemap_custom']['simple_sitemap_regenerate_now'] = array(
      '#type' => 'checkbox',
      '#title' => t('Regenerate sitemap after hitting Save'),
      '#description' => t('This setting will regenerate the whole sitemap including the above changes.<br/>Otherwise the sitemap will be rebuilt on next cron run.'),
      '#default_value' => FALSE,
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $custom_links_string = str_replace("\r\n", "\n", $form_state->getValue('custom_links'));
    $custom_links = array_filter(explode("\n", $custom_links_string), 'trim');

    foreach($custom_links as $link_setting) {
      $settings = explode(' ', $link_setting, 2);

      if (!\Drupal::service('path.validator')->isValid($settings[0])) {
        $form_state->setErrorByName('', t("The path <em>$settings[0]</em> does not exist."));
      }
      if ($settings[0][0] != '/') {
        $form_state->setErrorByName('', t("The path <em>$settings[0]</em> needs to start with an '/'."));
      }
      if (isset($settings[1])) {
        if (!is_numeric($settings[1]) || $settings[1] < 0 || $settings[1] > 1) {
          $form_state->setErrorByName('', t("Priority setting on line <em>$link_setting</em> is incorrect. Set priority from 0.0 to 1.0."));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $sitemap = new Simplesitemap;
    $custom_links_string = str_replace("\r\n", "\n", $form_state->getValue('custom_links'));
    $custom_links_string_lines = array_filter(explode("\n", $custom_links_string), 'trim');
    $custom_link_config = array();
    foreach($custom_links_string_lines as $line) {
      $line_settings = explode(' ', $line, 2);
      $custom_link_config[]['path'] = $line_settings[0];
      if (isset($line_settings[1])) {
        end($custom_link_config);
        $key = key($custom_link_config);
        $custom_link_config[$key]['priority'] = number_format((float)$line_settings[1], 1, '.', '');
      }
    }
    $sitemap->save_config('custom', $custom_link_config);

    parent::submitForm($form, $form_state);

    // Regenerate sitemaps according to user setting.
    if ($form_state->getValue('simple_sitemap_regenerate_now')) {
      $sitemap->generate_sitemap();
    }
  }
}
