<?php

namespace Drupal\openy\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines a form for selecting which content to import.
 */
class ContentSelectForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'openy_select_content';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, array &$install_state = NULL) {
    $form['#title'] = $this->t('Content');

    $options = [
      'landing' => $this->t('Demo Landing Pages'),
      'branches' => $this->t('Demo Branches'),
      'blog' => $this->t('Demo Blog Posts'),
      'programs' => $this->t('Demo Programs & Categories'),
    ];

    $form['content'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Choose content to import'),
      '#default_value' => ['landing', 'branches', 'blog', 'programs'],
      '#options' => $options,
    ];

    $form['actions'] = [
      'continue' => [
        '#type' => 'submit',
        '#value' => $this->t('Continue'),
      ],
      '#type' => 'actions',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $GLOBALS['install_state']['openy']['content'] = array_filter($form_state->getValue('content'));
  }

}
