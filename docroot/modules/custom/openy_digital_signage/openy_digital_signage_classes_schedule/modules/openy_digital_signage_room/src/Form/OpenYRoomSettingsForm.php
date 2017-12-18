<?php

namespace Drupal\openy_digital_signage_room\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides form for managing module settings.
 */
class OpenYRoomSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'openy_digital_signage_room_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['openy_digital_signage_room.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('openy_digital_signage_room.settings');
    $form['groupex_default_status'] = [
      '#type' => 'radios',
      '#title' => $this->t('Default room status when imported from GroupEx Pro'),
      '#default_value' => $config->get('groupex_default_status'),
      '#options' => [
        0 => $this->t('Disabled'),
        1 => $this->t('Enabled'),
      ],
    ];
    $form['personify_default_status'] = [
      '#type' => 'radios',
      '#title' => $this->t('Default room status when imported from Personify'),
      '#default_value' => $config->get('personify_default_status'),
      '#options' => [
        0 => $this->t('Disabled'),
        1 => $this->t('Enabled'),
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('openy_digital_signage_room.settings')
      ->set('groupex_default_status', $form_state->getValue('groupex_default_status'))
      ->set('personify_default_status', $form_state->getValue('personify_default_status'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
