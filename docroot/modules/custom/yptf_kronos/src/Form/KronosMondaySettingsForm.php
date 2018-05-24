<?php

namespace Drupal\yptf_kronos\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;

/**
 * Provides MindBody settings form.
 */
class KronosMondaySettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'yptf_kronos_monday_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['yptf_kronos_monday.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('yptf_kronos_monday.settings');

    $email_type = ['leadership' => 'Leadership email', 'pt_managers' => 'PT managers email'];
    $tokens = ['leadership' => '[leadership-report]', 'pt_managers' => '[pt-manager-report]'];

    foreach ($email_type as $id => $data) {
      $form[$id] = [
        '#type' => 'fieldset',
        '#title' => $this->t($data),
        '#description' => $this->t('Provide settings for the specified email type: %env', ['%env' => $data]),
      ];
      $form[$id][$id . ':enabled'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Enabled'),
        '#default_value' => !empty($config->get($id)['enabled']) ? $config->get($id)['enabled'] : '',
        '#description' => $this->t('Turn the checkbox on to enable Newsletter.'),
      );
      $form[$id][$id . ':disabled_message'] = array(
        '#type' => 'text_format',
        '#title' => t('Disabled message'),
        '#default_value' => !empty($config->get($id)['disabled_message']['value']) ? $config->get($id)['disabled_message']['value'] : '',
        '#description' => $this->t('Email with this message will be sent out when checkbox above is not checked. Leave empty to do not send message.'),
        '#format' => 'full_html',
      );
      $terms = \Drupal::entityTypeManager()
        ->getStorage('taxonomy_term')
        ->loadByProperties(['vid' => 'staff_type']);

      $options = [];
      foreach ($terms as $index => $term) {
        $options[$index] = $term->getName();
      }

      $form[$id][$id . ':staff_type'] = array(
        '#type' => 'select',
        '#options' => $options,
        '#title' => $this->t('Staff type'),
        '#default_value' => !empty($config->get($id)['staff_type']) ? $config->get($id)['staff_type'] : '',
        '#description' => $this->t('Choose roles of the recipients.'),
        '#size' => 10,
        '#multiple' => 'true',
        '#attributes' => ['style' => 'height:' . count($options) * 20 . 'px'],
      );
      $form[$id][$id . ':subject'] = array(
        '#type' => 'textfield',
        '#title' => t('Subject'),
        '#default_value' => !empty($config->get($id)['subject']) ? $config->get($id)['subject'] : '',
        '#description' => $this->t('Email subject. Tokens: [report-branch-name], [report-start-date], [report-end-date].'),
      );
      $form[$id][$id . ':body'] = array(
        '#type' => 'text_format',
        '#title' => t('Body'),
        '#default_value' => !empty($config->get($id)['body']['value']) ? $config->get($id)['body']['value'] : '',
        '#description' => $this->t('Token to use: %token. It will be replaced with appropriate report.', ['%token' => $tokens[$id]]),
        '#format' => 'full_html',
      );
    }
    $form['admin_emails'] = array(
      '#type' => 'textarea',
      '#title' => t('Administrator emais for Error report'),
      '#default_value' => !empty($config->get('admin_emails')) ? $config->get('admin_emails') : '',
      '#description' => $this->t('Admin emails. Comma-separated values.'),
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $email_type = ['leadership' => 'Leadership email', 'pt_managers' => 'PT managers email'];
    foreach ($email_type as $id => $data) {
      $this->config('yptf_kronos_monday.settings')->set($id, [
        'enabled' => $values[$id . ':enabled'],
        'staff_type' => $values[$id . ':staff_type'],
        'subject' => $values[$id . ':subject'],
        'body' => $values[$id . ':body'],
        'disabled_message' => $values[$id . ':disabled_message'],
      ])->save();
    }
    $this->config('yptf_kronos_monday.settings')->set('admin_emails', $values['admin_emails'])->save();
    parent::submitForm($form, $form_state);
  }

}
