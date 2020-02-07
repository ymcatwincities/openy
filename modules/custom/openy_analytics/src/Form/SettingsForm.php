<?php

namespace Drupal\openy_analytics\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Settings Form for openy_analytics.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'openy_analytics_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'openy_analytics.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('openy_programs_search.settings');

    $form_state->setCached(FALSE);

    $form['server_url'] = [
      '#type' => 'url',
      '#title' => $this->t('OpenY Analytics Server URL'),
      '#default_value' => $config->get('server_url'),
      '#description' => t('Url to send usage statistics'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /* @var $config \Drupal\Core\Config\Config */
    $config = \Drupal::service('config.factory')->getEditable('openy_analytics.settings');

    if ($server_url = $form_state->getValue('server_url')) {
      if (preg_match("#https?://#", $server_url) === 0) {
        $server_url = 'https://' . $server_url;
      }
      $config->set('server_url', $server_url)->save();
    }
    parent::submitForm($form, $form_state);

  }

}
