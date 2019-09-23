<?php

namespace Drupal\openy_myy\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Settings Form for OpenY MyY.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'openy_myy_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'openy_myy.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('openy_myy.settings');

    $form['childcare_purchase_link_wrapper'] = [
      '#type' => 'details',
      '#title' => t('Childcare Purchase Link'),
      '#description' => t('Provide link details for using in MyY application.'),
      '#open' => TRUE,
    ];

    $form['childcare_purchase_link_wrapper']['childcare_purchase_link_title'] = [
      '#type' => 'textfield',
      '#title' => t('Link Title'),
      '#default_value' => $config->get('childcare_purchase_link_title'),
    ];

    $form['childcare_purchase_link_wrapper']['childcare_purchase_link_url'] = [
      '#type' => 'textfield',
      '#title' => t('URL'),
      '#default_value' => $config->get('childcare_purchase_link_url'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /* @var $config \Drupal\Core\Config\Config */
    $config = \Drupal::service('config.factory')->getEditable('openy_myy.settings');

    $config->set('childcare_purchase_link_title', $form_state->getValue('childcare_purchase_link_title'))->save();
    $config->set('childcare_purchase_link_url', $form_state->getValue('childcare_purchase_link_url'))->save();

    parent::submitForm($form, $form_state);
  }

}
