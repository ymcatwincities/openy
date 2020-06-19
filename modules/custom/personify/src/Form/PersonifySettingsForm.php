<?php

namespace Drupal\personify\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Settings Form for OpenY PERSONIFY.
 */
class PersonifySettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'personify_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'personify.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('personify.settings');
    $form['environment'] = [
      '#type' => 'select',
      '#options' => [
        'prod' => 'Production',
        'stage' => 'Staging'
      ],
      '#default_value' => $config->get('environment'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    /* @var $config \Drupal\Core\Config\Config */
    $config = $this->config('personify.settings');

    $config->set('environment', $form_state->getValue('environment'))->save();

    parent::submitForm($form, $form_state);
  }

}
