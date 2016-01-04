<?php
/**
 * @file
 * Contains \Drupal\ymca_groupex\Form\FindClassesForm
 */

namespace Drupal\ymca_groupex\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements a FindClassesForm.
 */
class FindClassesForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'find_classes_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['hello'] = [
      '#markup' => 'Hello world!',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    drupal_set_message('Submitted');
  }

}