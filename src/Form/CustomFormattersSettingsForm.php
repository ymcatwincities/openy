<?php

namespace Drupal\custom_formatters\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure update settings for this site.
 */
class CustomFormattersSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'custom_formatters_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['custom_formatters.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('custom_formatters.settings');

    $form['label_prefix'] = array(
      '#type'          => 'checkbox',
      '#title'         => $this->t('Use Label prefix?'),
      '#description'   => $this->t('If checked, all Custom Formatters labels will be prefixed with a set value.'),
      '#default_value' => $config->get('label_prefix'),
    );

    $form['label_prefix_value'] = array(
      '#type'          => 'textfield',
      '#title'         => $this->t('Label prefix'),
      '#default_value' => $config->get('label_prefix_value'),
      '#states'        => array(
        'invisible' => array(
          'input[name="label_prefix"]' => array('checked' => FALSE),
        ),
      ),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('label_prefix') && empty($form_state->getValue('label_prefix_value'))) {
      $form_state->setErrorByName('label_prefix_value', $this->t('A label prefix must be defined if you wish to use the prefix.'));
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    drupal_set_message($this->t('Custom Formatters settings have been updated.'));

    $config = $this->config('custom_formatters.settings');
    $config
      ->set('label_prefix', $form_state->getValue('label_prefix'))
      ->set('label_prefix_value', $form_state->getValue('label_prefix_value'))
      ->save();

    // Clear cached formatters.
    // @TODO - Tag custom formatters?
    \Drupal::service('plugin.manager.field.formatter')
      ->clearCachedDefinitions();
  }

}
