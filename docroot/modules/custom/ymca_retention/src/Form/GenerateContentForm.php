<?php

namespace Drupal\ymca_retention\Form;

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
    module_load_include('inc', 'ymca_retention', 'includes/ymca_retention.members');
    _ymca_retention_members_create($form_state->getValue('options'));
  }

}
