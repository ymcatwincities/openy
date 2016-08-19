<?php

/**
 * @file
 * Contains \Drupal\stage_file_proxy\Form\SettingsForm.
 */

namespace Drupal\stage_file_proxy\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Unicode;

class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'stage_file_proxy_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'stage_file_proxy.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $field_type = NULL) {
    $config = $this->config('stage_file_proxy.settings');

    $form['origin'] = array(
      '#type' => 'textfield',
      '#title' => t('The origin website.'),
      '#default_value' => $config->get('origin'),
      '#description' => t("The origin website. For example: 'http://example.com' with no trailing slash. If the site is using HTTP Basic Authentication (the browser popup for username and password) you can embed those in the url. Be sure to URL encode any special characters:<br/><br/>For example, setting a user name of 'myusername' and password as, 'letme&in' the configuration would be the following: <br/><br/>'http://myusername:letme%26in@example.com';"),
      '#required' => FALSE,
    );

    $stage_file_proxy_origin_dir = $config->get('origin_dir');
    if (!$stage_file_proxy_origin_dir) {
      $stage_file_proxy_origin_dir = $config->get('file_public_path');
      if (empty($stage_file_proxy_origin_dir)) {
        $stage_file_proxy_origin_dir = \Drupal::service('site.path') . '/files';
      }
    }
    $form['origin_dir'] = array(
      '#type' => 'textfield',
      '#title' => t('The origin directory.'),
      '#default_value' => $stage_file_proxy_origin_dir,
      '#description' => t('If this is set then Stage File Proxy will use a different path for the remote files. This is useful for multisite installations where the sites directory contains different names for each url. If this is not set, it defaults to the same path as the local site.'),
      '#required' => FALSE,
    );

    $form['use_imagecache_root'] = array(
      '#type' => 'checkbox',
      '#title' => t('Imagecache Root.'),
      '#default_value' => $config->get('use_imagecache_root'),
      '#description' => t("If this is true (default) then Stage File Proxy will look for /imagecache/ in the URL and determine the original file and request that rather than the processed file, then send a header to the browser to refresh the image and let imagecache handle it. This will speed up future imagecache requests for the same original file."),
      '#required' => FALSE,
    );

    $form['hotlink'] = array(
      '#type' => 'checkbox',
      '#title' => t('Hotlink.'),
      '#default_value' => $config->get('hotlink'),
      '#description' => t("If this is true then Stage File Proxy will not transfer the remote file to the local machine, it will just serve a 301 to the remote file and let the origin webserver handle it."),
      '#required' => FALSE,
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  function validateForm(array &$form, FormStateInterface $form_state) {

    $origin = $form_state->getValue('origin');

    if (!empty($origin) && filter_var($origin, FILTER_VALIDATE_URL) === FALSE) {
      $form_state->setErrorByName('origin', 'Origin needs to be a valid URL.');
    }

    if (!empty($origin) && Unicode::substr($origin, -1) === '/') {
      $form_state->setErrorByName('origin', 'Origin URL cannot end in slash.');
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('stage_file_proxy.settings');

    $keys = array(
      'origin',
      'origin_dir',
      'use_imagecache_root',
      'hotlink',
    );
    foreach ($keys as $key) {
      $config->set($key, $form_state->getValue($key));
    }
    $config->save();
  }

}
