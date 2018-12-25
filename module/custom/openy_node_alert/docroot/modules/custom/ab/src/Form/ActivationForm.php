<?php

namespace Drupal\ab\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class ActivationForm.
 *
 * @package Drupal\ab\Form
 */
class ActivationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ab_activation_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['ab.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('ab.settings');
    $form['activate_ab_testing_framework'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Activate AB testing framework'),
      '#default_value' => !empty($config->get('ab')) ? $config->get('ab') : 0,
      '#description' => $this->t('Check if you need to start AB testing over a site. Disable otherwise.'),
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Submit'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('ab.settings')
      ->set('ab', $values['activate_ab_testing_framework'])
      ->save();

    parent::submitForm($form, $form_state);
    drupal_flush_all_caches();
  }

}
