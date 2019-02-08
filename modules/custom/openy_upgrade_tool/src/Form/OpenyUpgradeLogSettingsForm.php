<?php

namespace Drupal\openy_upgrade_tool\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class OpenyUpgradeLogSettingsForm.
 *
 * @ingroup openy_upgrade_tool
 */
class OpenyUpgradeLogSettingsForm extends FormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'openy_upgrade_log_settings';
  }

  /**
   * Defines the settings form for Openy upgrade log entities.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   Form definition array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('openy_upgrade_tool.settings');
    // TODO: Remove this in Open Y 3.0 release.
    $form['force_mode'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Force mode'),
      '#default_value' => $config->get('force_mode'),
      '#description' => $this->t('In force mode Open Y updates can override any customisation on site.'),
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory()
      ->getEditable('openy_upgrade_tool.settings')
      ->set('force_mode', $form_state->getValue('force_mode'))
      ->save();
  }

}
