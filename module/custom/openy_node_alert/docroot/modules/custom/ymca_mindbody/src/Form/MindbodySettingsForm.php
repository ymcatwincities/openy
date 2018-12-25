<?php

namespace Drupal\ymca_mindbody\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;

/**
 * Provides MindBody settings form.
 */
class MindbodySettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ymca_mindbody_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['ymca_mindbody.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('ymca_mindbody.settings');

    $form['disabled_form_block_id'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Disabled form Block ID'),
      '#default_value' => !empty($config->get('disabled_form_block_id')) ? $config->get('disabled_form_block_id') : '',
    );

    $form['hide_time'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Hide interval (minutes)'),
      '#default_value' => !empty($config->get('hide_time')) ? $config->get('hide_time') : 0,
      '#description' => $this->t('The amount of minutes when the nearest time slots will be hidden.'),
    );

    $form['failed_orders_recipients'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Recipients emails'),
      '#default_value' => !empty($config->get('failed_orders_recipients')) ? $config->get('failed_orders_recipients') : '',
      '#description' => $this->t('List of recipients emails for notifying of failed orders.'),
    );

    $form['pt_form_disabled'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Disable all PT forms'),
      '#default_value' => !empty($config->get('pt_form_disabled')) ? TRUE : FALSE,
      '#description' => $this->t('Turn the checkbox on to disable all Personal Training forms'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('ymca_mindbody.settings')
      ->set('disabled_form_block_id', $values['disabled_form_block_id'])
      ->set('pt_form_disabled', $values['pt_form_disabled'])
      ->set('hide_time', $values['hide_time'])
      ->set('failed_orders_recipients', $values['failed_orders_recipients'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
