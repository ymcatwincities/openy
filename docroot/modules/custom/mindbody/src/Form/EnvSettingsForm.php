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
    return ['mindbody.settings.env', 'mindbody.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('mindbody.settings');
    $configEnv = $this->config('mindbody.settings.env');

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
        $form[$id][$id . ':' . $field] = array(
          '#type' => 'textfield',
          '#title' => $this->t($title),
          '#default_value' => !empty($configEnv->get($id)[$field]) ? $configEnv->get($id)[$field] : '',
        );
      }
    }
    $options = $configEnv->getRawData();
    unset($options['active']);
    $options = array_combine(array_keys($options), array_keys($options));
    $form['active'] = [
      '#type' => 'select',
      '#options' => $options,
      '#title' => $this->t('Select active environment.'),
      '#weight' => -100,
      '#default_value' => $configEnv->get('active'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $active = $values['active'];
    // Set active values to current config.
    $this->config('mindbody.settings')
      ->set('sourcename', $values[$active . ':sourcename'])
      ->set('password', $values[$active . ':password'])
      ->set('site_id', $values[$active . ':site_id'])
      ->set('user_name', $values[$active . ':user_name'])
      ->set('user_password', $values[$active . ':user_password'])
      ->save();

    foreach ($this->config('mindbody.settings.env')->getRawData() as $id => $data) {
      if ($id == 'active') {
        $this->config('mindbody.settings.env')->set($id, $values['active']);
        continue;
      }
      $this->config('mindbody.settings.env')->set($id, [
        'sourcename' => $values[$id . ':sourcename'],
        'password' => $values[$id . ':password'],
        'site_id' => $values[$id . ':site_id'],
        'user_name' => $values[$id . ':user_name'],
        'user_password' => $values[$id . ':user_password'],
      ]);
    }
    $this->config('mindbody.settings.env')->save();

    parent::submitForm($form, $form_state);
  }

}
