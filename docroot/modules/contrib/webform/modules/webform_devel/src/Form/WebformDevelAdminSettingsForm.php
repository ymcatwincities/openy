<?php

namespace Drupal\webform_devel\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure webform devel admin settings for this site.
 */
class WebformDevelAdminSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webform_devel_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['webform_devel.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['logger'] = [
      '#type' => 'details',
      '#title' => $this->t('Logging and errors'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];
    $form['logger']['debug'] = [
      '#type' => 'radios',
      '#title' => $this->t("Display debugging notices and errors on screen"),
      '#default_value' => $this->config('webform_devel.settings')->get('logger.debug') ? '1' : '0',
      '#description' => $this->t("Checking 'Yes' will display PHP and theme notices onscreen."),
      '#options' => [
        '0' => $this->t('No'),
        '1' => $this->t('Yes'),
      ],
      '#required' => TRUE,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $values['logger']['debug'] = (boolean) $values['logger']['debug'];
    $this->config('webform_devel.settings')
      ->set('logger', $values['logger'])
      ->save();
    parent::submitForm($form, $form_state);
  }

}
