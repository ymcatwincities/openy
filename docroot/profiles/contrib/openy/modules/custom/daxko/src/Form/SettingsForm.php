<?php

namespace Drupal\daxko\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Settings Form for daxko.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'daxko_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'daxko.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('daxko.settings');

    $form_state->setCached(FALSE);

    /* @see \Drupal\daxko\DaxkoClientFactory::get */
    $form['client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Daxko Client ID'),
      '#default_value' => $config->get('client_id'),
      '#description' => t('Add your Daxko account id here. It is most likely a short number, like 1234.'),
    ];

    $form['base_uri'] = [
      '#type' => 'url',
      '#title' => $this->t('Daxko API Base URI'),
      '#default_value' => $config->get('base_uri'),
      '#description' => t('Add your Daxko API base uri here. It is most likely https://api.daxko.com/v1/.'),
    ];

    $form['auth_fieldset'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Daxko account information'),
      '#prefix' => '<div id="daxko-auth-fieldset-wrapper">',
      '#suffix' => '</div>',
    ];

    $form['auth_fieldset']['user'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Daxko account name'),
      '#default_value' => $config->get('user'),
      '#description' => t('Add your Daxko API user name here.'),
    ];

    $form['auth_fieldset']['pass'] = [
      '#type' => 'password',
      '#title' => $this->t('Daxko password'),
      '#description' => t('Add your Daxko API password.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /* @var $config \Drupal\Core\Config\Config */
    $config = \Drupal::service('config.factory')->getEditable('daxko.settings');

    $config->set('client_id', $form_state->getValue('client_id'))->save();

    if ($base_uri = $form_state->getValue('base_uri')) {
      if (preg_match("#https?://#", $base_uri) === 0) {
        $base_uri = 'https://' . $base_uri;
      }
      $config->set('base_uri', rtrim($base_uri, '/') . '/')->save();
    }

    $config->set('user', $form_state->getValue('user'))->save();

    if (empty($pass = $form_state->getValue('pass'))) {
      $pass = $config->get('pass');
    }
    $config->set('pass', $pass)->save();

    parent::submitForm($form, $form_state);
  }

}
