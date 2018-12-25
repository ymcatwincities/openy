<?php

namespace Drupal\fullcalendar\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * @todo.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'fullcalendar_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['fullcalendar.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('fullcalendar.settings');

    $form['path'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Path to FullCalendar'),
      '#default_value' => $config->get('path'),
      '#description' => $this->t('Enter the path relative to Drupal root where the FullCalendar plugin directory is located.'),
    );
    $form['compression'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Choose FullCalendar compression level'),
      '#options' => array(
        'min' => $this->t('Production (Minified)'),
        'none' => $this->t('Development (Uncompressed code)'),
      ),
      '#default_value' => $config->get('compression'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('fullcalendar.settings')
      ->set('path', rtrim($form_state->getValue('path'), '/'))
      ->set('compression', $form_state->getValue('compression'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
