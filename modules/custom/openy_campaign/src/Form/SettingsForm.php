<?php

namespace Drupal\openy_campaign\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides form for managing module settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'openy_campaign_general_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['openy_campaign.general_settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('openy_campaign.general_settings');

    $form['error_msg_membership_id'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Error Message: Incorrect Membership ID'),
      '#default_value' => $config->get('error_msg_membership_id'),
      '#description' => $this->t('Message to display if user is trying to register/login with an incorrect membership ID. Example:
        Please check your member ID to ensure it has been entered correctly. If you have entered your number correctly 
        and it still is not working, please contact our customer service center at 612-230-9622.
      '),
    ];

    $form['error_msg_checkins_not_started'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Error Message: Registered previously member, but the campaign challenges have not yet started'),
      '#default_value' => $config->get('error_msg_checkins_not_started'),
      '#description' => $this->t('Message to display when member is already registered previously, 
        but the campaign challenges have not yet started. Example:
        Good news! Our records show you have already registered.
        If you think this is a mistake, please contact our customer service center at 612-230-9622.
      '),
    ];

    $form['error_msg_member_is_inactive'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Error Message: Member is inactive'),
      '#default_value' => $config->get('error_msg_member_is_inactive'),
      '#description' => $this->t('Message to display if the member ID entered is inactive. Example:
        Our records indicate the member ID entered is inactive. Please check your member ID and
        re-enter it, or contact the customer service center at 612-230-9622.
      '),
    ];

    $form['error_msg_target_audience_settings'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Error Message: Member is ineligible due to the Target Audience Setting'),
      '#default_value' => $config->get('error_msg_target_audience_settings'),
      '#description' => $this->t('Message to display if member is ineligible due to the Target Audience Setting. Example:
        We\'re sorry, but you are not eligible to participate in this promotion. Please check the rules page for details.
        If you have any questions, please contact our customer service center at 612-230-9622.
      '),
    ];

    $form['error_msg_default'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Error Message: Default message'),
      '#default_value' => $config->get('error_msg_default'),
      '#description' => $this->t('Message to display in all other cases. Example: 
        Something went wrong. Please contact our customer service center at 612-230-9622.
      '),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('openy_campaign.general_settings');
    $config->set('error_msg_membership_id', $form_state->getValue('error_msg_membership_id'));
    $config->set('error_msg_checkins_not_started', $form_state->getValue('error_msg_checkins_not_started'));
    $config->set('error_msg_member_is_inactive', $form_state->getValue('error_msg_member_is_inactive'));
    $config->set('error_msg_target_audience_settings', $form_state->getValue('error_msg_target_audience_settings'));
    $config->set('error_msg_default', $form_state->getValue('error_msg_default'));
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
