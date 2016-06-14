<?php

namespace Drupal\mindbody\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;

/**
 * Provides MindBody settings form.
 */
class EnvSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mindbody_env_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['mindbody.env.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('mindbody.settings');
    $configEnv = $this->config('mindbody.env.settings');

    $fields = [
      'sourcename' => 'Sourcename',
      'password' => 'Password',
      'site_id' => 'Site ID',
      'user_name' => 'User name',
      'user_password' => 'User password',
    ];
    foreach ($configEnv->getRawData() as $id => $data) {
      if ($id == 'active') {
        continue;
      }
      $form[$id] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Environment: %env', ['%env' => $id]),
        '#description' => $this->t('Provide settings for the specified environment: %env', ['%env' => $id])
      ];
      foreach ($fields as $field => $title) {
        $form[$id][$field] = array(
          '#type' => 'textfield',
          '#title' => $this->t($title),
          '#default_value' => !empty($configEnv->get($id)[$field]) ? $configEnv->get($id)[$field] : '',
        );
      }
    }
    $options = $configEnv->getRawData();
    unset($options['active']);
    $options = array_keys($options);
    $form['active'] = [
      '#type' => 'options',
      '#options' => $options,
      '#title' => $this->t('Select active environment.')
    ];

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
