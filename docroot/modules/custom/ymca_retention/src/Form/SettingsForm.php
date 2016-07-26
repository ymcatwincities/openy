<?php

namespace Drupal\ymca_retention\Form;

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
    return 'ymca_retention_general_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['ymca_retention.general_settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('ymca_retention.general_settings');

    $form['date_campaign_open'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Campaign open date and time'),
      '#size' => 50,
      '#maxlength' => 500,
      '#default_value' => $config->get('date_campaign_open'),
      '#description' => $this->t('Date and time when campaign will be open.'),
    ];

    $form['date_campaign_close'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Campaign close date and time'),
      '#size' => 50,
      '#maxlength' => 500,
      '#default_value' => $config->get('date_campaign_close'),
      '#description' => $this->t('Date and time when campaign will be closed.'),
    ];

    $form['date_registration_open'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Registration open date and time'),
      '#size' => 50,
      '#maxlength' => 500,
      '#default_value' => $config->get('date_registration_open'),
      '#description' => $this->t('Date and time when registration will be open.'),
    ];

    $form['date_registration_close'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Registration close date and time'),
      '#size' => 50,
      '#maxlength' => 500,
      '#default_value' => $config->get('date_registration_close'),
      '#description' => $this->t('Date and time when registration will be closed.'),
    ];

    $form['date_reporting_open'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Reporting open date and time'),
      '#size' => 50,
      '#maxlength' => 500,
      '#default_value' => $config->get('date_reporting_open'),
      '#description' => $this->t('Date and time when reporting will be open.'),
    ];

    $form['date_reporting_close'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Reporting close date and time'),
      '#size' => 50,
      '#maxlength' => 500,
      '#default_value' => $config->get('date_reporting_close'),
      '#description' => $this->t('Date and time when reporting will be closed.'),
    ];

    $form['new_member_goal_number'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Goal of visits for new members'),
      '#size' => 50,
      '#maxlength' => 500,
      '#default_value' => $config->get('new_member_goal_number'),
      '#description' => $this->t('Goal of visits in this campaign for new members.'),
    ];

    $form['limit_goal_number'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Limit goal of visits'),
      '#size' => 50,
      '#maxlength' => 500,
      '#default_value' => $config->get('limit_goal_number'),
      '#description' => $this->t('Limit goal of visits in this campaign.'),
    ];

    $form['date_checkins_start'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Check-ins start date and time'),
      '#size' => 50,
      '#maxlength' => 500,
      '#default_value' => $config->get('date_checkins_start'),
      '#description' => $this->t('Start date and time, for getting data about check-ins in past months, before the campaign starts.'),
    ];

    $form['date_checkins_end'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Check-ins end date and time'),
      '#size' => 50,
      '#maxlength' => 500,
      '#default_value' => $config->get('date_checkins_end'),
      '#description' => $this->t('End date and time, for getting data about check-ins in past months, before the campaign starts.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('ymca_retention.general_settings')
      ->set('date_campaign_open', $form_state->getValue('date_campaign_open'))
      ->set('date_campaign_close', $form_state->getValue('date_campaign_close'))
      ->set('date_registration_open', $form_state->getValue('date_registration_open'))
      ->set('date_registration_close', $form_state->getValue('date_registration_close'))
      ->set('date_reporting_open', $form_state->getValue('date_reporting_open'))
      ->set('date_reporting_close', $form_state->getValue('date_reporting_close'))
      ->set('new_member_goal_number', $form_state->getValue('new_member_goal_number'))
      ->set('limit_goal_number', $form_state->getValue('limit_goal_number'))
      ->set('date_checkins_start', $form_state->getValue('date_checkins_start'))
      ->set('date_checkins_end', $form_state->getValue('date_checkins_end'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
