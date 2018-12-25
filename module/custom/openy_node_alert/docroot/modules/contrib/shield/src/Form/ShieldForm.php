<?php

/**
 * @file
 * Contains \Drupal\shield\Form\ShieldForm.
 */

namespace Drupal\shield\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure http access for this site.
 */
class ShieldForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['shield.config'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'shield_config_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('shield.config');

    $form['enabled'] = array(
      '#type' => 'checkbox',
      '#title' => 'enabled',
      '#default_value' => $config->get('enabled'),
    );
    $form['login'] = array(
      '#type' => 'textfield',
      '#title' => 'login',
      '#default_value' => $config->get('login'),
    );
    $form['password'] = array(
      '#type' => 'textfield',
      '#title' => 'password',
      '#default_value' => $config->get('password'),
    );
    $form['message'] = array(
      '#type' => 'textfield',
      '#title' => 'message',
      '#default_value' => $config->get('message'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $options = array('enabled', 'login', 'password', 'message');

    $config = $this->config('shield.config');

    foreach ($options as $option) {
      $val = $form_state->getValue($option);
      $config->set($option, $val);
    }

    $config->save();

    parent::submitForm($form, $form_state);
  }

}
