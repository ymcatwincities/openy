<?php
/**
 * @file
 * This is the GlobalRedirect admin include which provides an interface to global redirect to change some of the default settings
 * Contains \Drupal\globalredirect\Form\GlobalredirectSettingsForm.
 */

namespace Drupal\globalredirect\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Defines a form to configure module settings.
 */
class GlobalredirectSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'globalredirect_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['globalredirect.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Get all settings
    $config = $this->config('globalredirect.settings');
    $settings = $config->get();

    $form['settings'] = array(
      '#tree' => TRUE,
    );

    $form['settings']['deslash'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Deslash'),
      '#description' => $this->t('If enabled, this option will remove the trailing slash from requests. This stops requests such as <code>example.com/node/1/</code> failing to match the corresponding alias and can cause duplicate content. On the other hand, if you require certain requests to have a trailing slash, this feature can cause problems so may need to be disabled.'),
      '#default_value' => $settings['deslash'],
    );

    $form['settings']['nonclean_to_clean'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Non-clean to Clean'),
      '#description' => $this->t('If enabled, this option will redirect from non-clean to clean URL (if Clean URL\'s are enabled). This will stop, for example, node 1  existing on both <code>example.com/node/1</code> AND <code>example.com/index.php/node/1</code>.'),
      '#default_value' => $settings['nonclean_to_clean'],
    );

    $form['settings']['access_check'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Check access to the redirected page'),
      '#description' => $this->t('This helps to stop redirection on protected pages and avoids giving away <em>secret</em> URL\'s. <strong>By default this feature is disabled to avoid any unexpected behavior</strong>'),
      '#default_value' => $settings['access_check'],
    );

    $form['settings']['normalize_aliases'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Normalize aliases'),
      '#description' => $this->t('Will check if for the given path an alias exists or if the used alias is in correct case and will redirect to the appropriate alias form.'),
      '#default_value' => $settings['normalize_aliases'],
    );

    $form['settings']['canonical'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Add Canonical Link'),
      '#description' => $this->t('If enabled, will add a <a href="!canonical">canonical link</a> to each page.', array('!canonical' => 'http://googlewebmastercentral.blogspot.com/2009/02/specify-your-canonical.html')),
      '#default_value' => $settings['canonical'],
    );


    $form['settings']['content_location_header'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Set Content Location Header'),
      '#description' => $this->t('If enabled, will add a <a href="!canonical">Content-Location</a> header.', array('!canonical' => 'http://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.14')),
      '#default_value' => $settings['content_location_header'],
    );


    $form['settings']['term_path_handler'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Taxonomy Term Path Handler'),
      '#description' => $this->t('If enabled, any request to a taxonomy/term/[tid] page will check that the correct path is being used for the term\'s vocabulary.'),
      '#default_value' => $settings['term_path_handler'],
    );

    $form['settings']['frontpage_redirect'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Frontpage Redirect Handler'),
      '#description' => $this->t('If enabled, any request to the frontpage path will redirect to the site root.<br />
                           Whatever you set as the path of the front page on the !link settings page will redirect to the site root (e.g. "node" or "node/1" and also its alias (e.g. in case you have set "node/1" as your home page but that page also has an alias "home")).', array(
        '!link' => $this->l($this->t('Site Information'), Url::fromRoute('system.site_information_settings')),
      )),
      '#default_value' => $settings['frontpage_redirect'],
    );

    $form['settings']['ignore_admin_path'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Ignore Admin Path'),
      '#description' => $this->t('If enabled, any request to the admin section of the site will be ignored by Global Redirect.<br />
                           This is useful if you are experiencing problems with Global Redirect and want to protect the admin section of your website. NOTE: This may not be desirable if you are using path aliases for certain admin URLs.'),
      '#default_value' => $settings['ignore_admin_path'],
    );

    $form['buttons']['reset'] = array(
      '#type' => 'submit',
      '#submit' => array('::submitResetDefaults'),
      '#value' => t('Reset to defaults'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * Compares the submitted settings to the defaults and unsets any that are equal. This was we only store overrides.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Get config factory
    $config = $this->config('globalredirect.settings');
    $form_values = $form_state->getValue(['settings']);

    $config
      ->set('deslash', $form_values['deslash'])
      ->set('nonclean_to_clean', $form_values['nonclean_to_clean'])
      ->set('access_check', $form_values['access_check'])
      ->set('normalize_aliases', $form_values['normalize_aliases'])
      ->set('canonical', $form_values['canonical'])
      ->set('content_location_header', $form_values['content_location_header'])
      ->set('term_path_handler', $form_values['term_path_handler'])
      ->set('frontpage_redirect', $form_values['frontpage_redirect'])
      ->set('ignore_admin_path', $form_values['ignore_admin_path'])
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Clears the caches.
   */
  public function submitResetDefaults(array &$form, FormStateInterface $form_state) {
    $config = $this->config('globalredirect.settings');

    // Get config factory
    $settingsDefault = $this->getDefaultSettings();

    $config
      ->set('deslash', $settingsDefault['deslash'])
      ->set('nonclean_to_clean', $settingsDefault['nonclean_to_clean'])
      ->set('access_check', $settingsDefault['access_check'])
      ->set('normalize_aliases', $settingsDefault['normalize_aliases'])
      ->set('canonical', $settingsDefault['canonical'])
      ->set('content_location_header', $settingsDefault['content_location_header'])
      ->set('term_path_handler', $settingsDefault['term_path_handler'])
      ->set('frontpage_redirect', $settingsDefault['frontpage_redirect'])
      ->set('ignore_admin_path', $settingsDefault['ignore_admin_path'])
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Returns an associative array of default settings
   * @return array
   */
  public function getDefaultSettings() {

    $defaults = array(
      'deslash' => 1,
      'nonclean_to_clean' => 1,
      'access_check' => 0,
      'normalize_aliases' => 1,
      'canonical' => 0,
      'content_location_header' => 0,
      'term_path_handler' => 1,
      'frontpage_redirect' => 1,
      'ignore_admin_path' => 1,
    );

    return $defaults;
  }

}
