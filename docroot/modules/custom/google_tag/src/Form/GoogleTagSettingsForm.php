<?php

/**
 * @file
 * Contains \Drupal\google_tag\Form\GoogleTagSettingsform.
 */

namespace Drupal\google_tag\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class GoogleTagSettingsForm
 * @package Drupal\google_tag\Form
 */
class GoogleTagSettingsForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'google_tag_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['google_tag.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('google_tag.settings');

    // Build form elements.
    $form['settings'] = [
      '#type' => 'vertical_tabs',
      '#attributes' => ['class' => ['google-tag']],
      '#attached' => [
        'library' => ['google_tag/drupal.settings_form'],
      ],
    ];

    // General tab
    $form['general'] = [
      '#type' => 'details',
      '#title' => $this->t('General'),
      '#group' => 'settings',
    ];

    $form['general']['container_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Container ID'),
      '#description' => $this->t('The ID assigned by Google Tag Manager (GTM) for this website container. To get a container ID, <a href="http://www.google.com/tagmanager/web/">sign up for GTM</a> and create a container for your website.'),
      '#default_value' => $config->get('container_id'),
      '#attributes' => ['placeholder' => ['GTM-xxxxxx']],
      '#size' => 11,
      '#maxlength' => 15,
      '#required' => TRUE,
    ];

    // Page paths tab
    $description = $this->t('On this and the following tab, specify the conditions on which the GTM JavaScript snippet will either be included in or excluded from the page response, thereby enabling or disabling tracking and other analytics.');
    $description .= $this->t(' All conditions must be satisfied for the snippet to be included. The snippet will be excluded if any condition is not met.<br /><br />');
    $description .= $this->t(' On this tab, specify the path condition.');

    $form['paths'] = [
      '#type' => 'details',
      '#title' => $this->t('Page paths'),
      '#group' => 'settings',
      '#description' => $description,
    ];

    $form['paths']['path_toggle'] = [
      '#type' => 'radios',
      '#title' => $this->t('Add snippet on specific paths'),
      '#options' => [
        GOOGLE_TAG_DEFAULT_INCLUDE => $this->t('All paths except the listed paths'),
        GOOGLE_TAG_DEFAULT_EXCLUDE => $this->t('Only the listed paths'),
      ],
      '#default_value' => $config->get('path_toggle'),
    ];
    $form['paths']['path_list'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Listed paths'),
      '#description' => $this->t('Enter one relative path per line using the "*" character as a wildcard. Example paths are: "%blog" for the blog page, "%blog-wildcard" for each individual blog, and "%front" for the front page.', ['%blog' => 'blog', '%blog-wildcard' => 'blog/*', '%front' => '<front>']),
      '#default_value' => $config->get('path_list'),
      '#rows' => 10,
    ];

    // User roles tab
    $form['roles'] = [
      '#type' => 'details',
      '#title' => $this->t('User roles'),
      '#description' => $this->t('On this tab, specify the user role condition.'),
      '#group' => 'settings',
    ];

    $form['roles']['role_toggle'] = [
      '#type' => 'radios',
      '#title' => t('Add snippet for specific roles'),
      '#options' => [
        GOOGLE_TAG_DEFAULT_INCLUDE => $this->t('All roles except the selected roles'),
        GOOGLE_TAG_DEFAULT_EXCLUDE => $this->t('Only the selected roles'),
      ],
      '#default_value' => $config->get('role_toggle'),
    ];

    $user_roles = array_map(function($role) {
      return $role->label();
    }, user_roles());

    $form['roles']['role_list'] = [
      '#type' => 'checkboxes',
      '#title' => t('Selected roles'),
      '#default_value' => $config->get('role_list'),
      '#options' => $user_roles,
    ];

    // Status tab
    $list_description = t('Enter one response status per line. For more information, refer to the <a href="http://en.wikipedia.org/wiki/List_of_HTTP_status_codes">list of HTTP status codes</a>.');

    $form['statuses'] = [
      '#type' => 'details',
      '#title' => $this->t('Response statuses'),
      '#group' => 'settings',
      '#description' => t('On this tab, specify the page response status condition. If enabled, this condition overrides the page path condition. In other words, if the HTTP response status is one of the listed statuses, then the page path condition is ignored.'),
    ];


    $form['statuses']['status_toggle'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Override path condition for listed response statuses'),
      '#description' => $this->t('If checked, then the path condition will be ingored for a listed page response status.'),
      '#default_value' => $config->get('status_toggle'),
    ];

    $form['statuses']['status_list'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Listed statuses'),
      '#description' => $list_description,
      '#default_value' => $config->get('status_list'),
      '#rows' => 5,
    ];

    // Advanced tab
    $form['advanced'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced'),
      '#group' => 'settings',
    ];

    $form['advanced']['compact_tag'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Compact the JavaScript snippet'),
      '#description' => $this->t('If checked, then the JavaScript snippet will be compacted to remove unnecessary whitespace. This is <strong>recommended on production sites</strong>. Leave unchecked to output a snippet that can be examined using a JavaScript debugger in the browser.'),
      '#default_value' => $config->get('compact_tag'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Trim the text values.
    $container_id = trim($form_state->getValue('container_id'));
    $form_state->setValue('path_list', trim($form_state->getValue('path_list')));
    $form_state->setValue('status_list', trim($form_state->getValue('status_list')));

    // Replace all types of dashes (n-dash, m-dash, minus) with a normal dash.
    $container_id = str_replace(['–', '—', '−'], '-', $container_id);

    if (!preg_match('/^GTM-\w{4,}$/', $container_id)) {
      // @todo Is there a more specific regular expression that applies?
      // @todo Is there a way to "test the connection" to determine a valid ID for
      // a container? It may be valid but not the correct one for the website.
      $form_state->setError($form['general']['container_id'], $this->t('A valid container ID is case sensitive and formatted like GTM-xxxxxx.'));
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('google_tag.settings')
      ->set('container_id', $form_state->getValue('container_id'))
      ->set('path_toggle', $form_state->getValue('path_toggle'))
      ->set('path_list', $form_state->getValue('path_list'))
      ->set('role_toggle', $form_state->getValue('role_toggle'))
      ->set('role_list', $form_state->getValue('role_list'))
      ->set('status_toggle', $form_state->getValue('status_toggle'))
      ->set('status_list', $form_state->getValue('status_list'))
      ->set('compact_tag', $form_state->getValue('compact_tag'))
      ->save();

    parent::submitForm($form, $form_state);
  }


}
