<?php

/**
 * @file
 * Contains \Drupal\simple_sitemap\Form\SimplesitemapCustomLinksForm.
 */

namespace Drupal\simple_sitemap\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

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

    $sitemap = \Drupal::service('simple_sitemap.generator');
    $setting_string = '';
    foreach ($sitemap->getConfig('custom') as $custom_link) {

      // todo: remove this statement after removing the index key from the configuration.
      if (isset($custom_link['index']) && $custom_link['index'] == 0)
        continue;

      $setting_string .= isset($custom_link['priority']) ? $custom_link['path'] . ' ' . $custom_link['priority'] : $custom_link['path'];
      $setting_string .= "\r\n";
    }

    $form['simple_sitemap_custom'] = array(
      '#title' => t('Custom links'),
      '#type' => 'fieldset',
      '#markup' => '<p>' . t('Add custom internal drupal paths to the XML sitemap.') . '</p>',
    );

    $form['simple_sitemap_custom']['custom_links'] = array(
      '#type' => 'textarea',
      '#title' => t('Relative Drupal paths'),
      '#default_value' => $setting_string,
      '#description' => t("Please specify drupal internal (relative) paths, one per line. Do not forget to prepend the paths with a '/'. You can optionally add a priority (0.0 - 1.0) by appending it to the path after a space. The home page with the highest priority would be <em>/ 1.0</em>, the contact page with the default priority would be <em>/contact 0.5</em>."),
    );

    $form['simple_sitemap_custom']['simple_sitemap_regenerate_now'] = array(
      '#type' => 'checkbox',
      '#title' => t('Regenerate sitemap after hitting <em>Save</em>'),
      '#description' => t('This setting will regenerate the whole sitemap including the above changes.'),
      '#default_value' => FALSE,
    );
    if ($sitemap->getSetting('cron_generate')) {
      $form['simple_sitemap_custom']['simple_sitemap_regenerate_now']['#description'] .= '</br>' . t('Otherwise the sitemap will be regenerated on the next cron run.');
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    $custom_link_config = $this->getCustomLinks($form_state->getValue('custom_links'));

    foreach($custom_link_config as $link_config) {

      if (!\Drupal::service('path.validator')->isValid($link_config['path'])) {
        $form_state->setErrorByName('', t("The path <em>@path</em> does not exist.", array('@path' => $link_config['path'])));
      }
      if ($link_config['path'][0] != '/') {
        $form_state->setErrorByName('', t("The path <em>@path</em> needs to start with a '/'.", array('@path' => $link_config['path'])));
      }
      if (isset($link_config['priority'])) {
        if (!is_numeric($link_config['priority']) || $link_config['priority'] < 0 || $link_config['priority'] > 1) {
          $form_state->setErrorByName('', t("The priority setting on line <em>@priority</em> is incorrect. Set the priority from 0.0 to 1.0.", array('@priority' => $link_config['priority'])));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $sitemap = \Drupal::service('simple_sitemap.generator');
    $custom_link_config = $this->getCustomLinks($form_state->getValue('custom_links'));
    foreach($custom_link_config as &$link_config) {
      if (isset($link_config['priority'])) {
        $link_config['priority'] = number_format((float)$link_config['priority'], 1, '.', '');
      }
    }
    $sitemap->saveConfig('custom', $custom_link_config);
    parent::submitForm($form, $form_state);

    // Regenerate sitemaps according to user setting.
    if ($form_state->getValue('simple_sitemap_regenerate_now')) {
      $sitemap->generateSitemap();
    }
  }

  private function getCustomLinks($custom_links_string) {
    $custom_links_string_lines = array_filter(explode("\n", str_replace("\r\n", "\n", $custom_links_string)), 'trim');
    $custom_link_config = array();
    foreach($custom_links_string_lines as $i => $line) {
      $line_settings = explode(' ', $line, 2);
      $custom_link_config[$i]['path'] = $line_settings[0];
      if (isset($line_settings[1])) {
        $custom_link_config[$i]['priority'] = $line_settings[1];
      }
    }
    return $custom_link_config;
  }
}
