<?php

namespace Drupal\ymca_retention\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides form for calculating member goals.
 */
class CalculateGoalsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ymca_retention_calculate_goals';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Calculate goals'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $operations = [
      [
        [
          '\Drupal\ymca_retention\Controller\MembersController',
          'calculateGoalsProcessBatch',
        ],
        [],
      ],
    ];
    $batch = [
      'title' => $this->t('Processing members'),
      'operations' => $operations,
      'finished' => [
        '\Drupal\ymca_retention\Controller\MembersController',
        'calculateGoalsFinishBatch',
      ],
    ];
    batch_set($batch);
  }

}
