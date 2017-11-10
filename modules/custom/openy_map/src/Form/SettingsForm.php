<?php

namespace Drupal\openy_map\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Admin settings Form for openy_map form.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'openy_map_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'openy_map.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('openy_map.settings');

    $form_state->setCached(FALSE);

    $tags = $config->get('default_tags');
    $form['default_tags'] = [
      '#type' => 'checkboxes',
      '#options' => [
        'YMCA' => $this->t('YMCA'),
        'Camps' => $this->t('Camps'),
        'Facilities' => $this->t('Facilities')
      ],
      '#title' => $this->t('Default filter tags for map'),
      '#description' => t('Enabled by default filter tags for map.'),
      '#default_value' => $tags,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /* @var $config \Drupal\Core\Config\Config */
    $config = \Drupal::service('config.factory')->getEditable('openy_map.settings');

    $config->set('default_tags', $form_state->getValue('default_tags'))->save();

    parent::submitForm($form, $form_state);
  }

}
