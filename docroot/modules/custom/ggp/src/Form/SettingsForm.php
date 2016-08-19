<?php

namespace Drupal\gardengnome_player\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a form that configures gardengnome player settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'gardengnome_player_admin_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'gardengnome_player.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('gardengnome_player.settings');

    $form['path'] = array(
      '#type' => 'textfield',
      '#title' => t('Extracted files location'),
      '#description' => t('Provide a path where the extracted files should be stored. Has to be accessible by via HTTP.'),
      '#default_value' => $config->get('path'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('gardengnome_player.settings')
      ->set('path', $values['path'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
