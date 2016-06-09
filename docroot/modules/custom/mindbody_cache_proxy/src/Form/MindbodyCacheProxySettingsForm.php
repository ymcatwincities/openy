<?php

namespace Drupal\mindbody_cache_proxy\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Settings form.
 */
class MindbodyCacheProxySettingsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'mindbody_cache_proxy_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['stats'] = array(
      '#title' => t('Cache statistics'),
      '#type' => 'fieldset',
    );

    $form['stats']['requests'] = [
      '#markup' => $this->t('Todo...'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
