<?php

namespace Drupal\activenet_registration\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Settings Form for activenet communities registration.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'activenet_registration_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'activenet_registration.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('activenet_registration.settings');

    $form_state->setCached(FALSE);

    $form['base_uri'] = [
      '#type' => 'url',
      '#title' => $this->t('Active Communities base URL'),
      '#default_value' => $config->get('base_uri'),
      '#description' => t('Your Activity Communities base URL. It follows the format of https://apm.activecommunities.com/{organization name}.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /* @var $config \Drupal\Core\Config\Config */
    $config = $this->configFactory->getEditable('activenet_registration.settings');
    
    $config->set('api_key', $form_state->getValue('api_key'))->save();
    if ($base_uri = $form_state->getValue('base_uri')) {
      if (preg_match("#https?://#", $base_uri) === 0) {
        $base_uri = 'https://' . $base_uri;
      }
      $config->set('base_uri', rtrim($base_uri, '/') . '/')->save();
    }

    parent::submitForm($form, $form_state);
  }

}
