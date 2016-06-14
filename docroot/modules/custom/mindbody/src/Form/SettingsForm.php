<?php

namespace Drupal\mindbody\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;

/**
 * Provides MindBody settings form.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mindbody_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['mindbody.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('mindbody.settings');
    
    $fields = [
      'sourcename' => 'Sourcename',
      'password' => 'Password',
      'site_id' => 'Site ID',
      'user_name' => 'User name',
      'user_password' => 'User password',
    ];

    foreach ($fields as $field => $title) {
      $form[$field] = array(
        '#type' => 'textfield',
        '#title' => $this->t($title),
        '#default_value' => !empty($config->get($field)) ? $config->get($field) : '',
      );
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('mindbody.settings')
      ->set('sourcename', $values['sourcename'])
      ->set('password', $values['password'])
      ->set('site_id', $values['site_id'])
      ->set('user_name', $values['user_name'])
      ->set('user_password', $values['user_password'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
