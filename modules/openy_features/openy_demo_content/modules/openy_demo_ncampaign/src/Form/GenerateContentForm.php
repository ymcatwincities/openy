<?php

namespace Drupal\openy_demo_ncampaign\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides form for generating content: members, checking, bonuses.
 */
class GenerateContentForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ymca_retention_generate_content';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['options'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Options:'),
      '#options' => ['everyday' => 'Generate checkin for each day of campaign'],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Create 10 test members with checkins and bonuses'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    module_load_include('inc', 'openy_demo_ncampaign', 'includes/openy_demo_ncampaign.members');
    _openy_demo_ncampaign_members_create($form_state->getValue('options'));
  }

}
