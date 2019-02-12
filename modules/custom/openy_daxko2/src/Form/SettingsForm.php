<?php

namespace Drupal\openy_daxko2\Form;

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
    return 'openy_daxko2_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'openy_daxko2.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('openy_daxko2.settings');

    $form_state->setCached(FALSE);

    /* @see \Drupal\daxko\DaxkoClientFactory::get */
    
    $form['base_uri'] = [
      '#type' => 'url',
      '#title' => $this->t('Daxko API Base URI v3'),
      '#default_value' => $config->get('base_uri'),
      '#description' => t('Add your Daxko API base uri here. It is most likely https://api.daxko.com/v3/.'),
    ];

    $form['client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client Id'),
      '#default_value' => $config->get('client_id'),
      '#description' => t('Your Daxko client id. Like 4032.'),
    ];

    $form['user'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Daxko account name'),
      '#default_value' => $config->get('user'),
      '#description' => t('Add your Daxko API user name here.'),
    ];

    $form['pass'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Daxko password'),
      '#default_value' => $config->get('pass'),
      '#description' => t('Add your Daxko API password.'),
    ];

    $form['referesh_token'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Daxko refresh token'),
      '#default_value' => $config->get('referesh_token'),
      '#description' => t('Refresh token is a large string like 241 chars long.'),
    ];

    $form['locations'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Locations'),
      '#default_value' => $config->get('locations'),
      '#description' => t('Locations mapping. One per row. "<location id>,<location name>". To group Locations wrap name of the location with asterix: *Camps*.'),
    ];

    $form['categories'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Categories'),
      '#default_value' => $config->get('categories'),
      '#description' => t('Categories. One per row. "<category id>,<category name>". To group categories wrap name of the category with asterix: *Swimming*.'),
    ];

    $form['percentage'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Percentage'),
      '#default_value' => !empty($config->get('percentage')) ? $config->get('percentage') : 100,
      '#description' => t('Percent of similarity between Session and Program names for displaying (100 means only identical names will be combined).'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /* @var $config \Drupal\Core\Config\Config */
    $config = \Drupal::service('config.factory')->getEditable('openy_daxko2.settings');

    $config->set('referesh_token', $form_state->getValue('referesh_token'))->save();

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

    $config->set('locations', $form_state->getValue('locations'))->save();
    $config->set('categories', $form_state->getValue('categories'))->save();
    $config->set('percentage', $form_state->getValue('percentage'))->save();

    parent::submitForm($form, $form_state);
  }

}
