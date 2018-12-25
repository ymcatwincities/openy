<?php

namespace Drupal\groupex_form_cache\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class GroupexFormCacheSettingsForm.
 *
 * @package Drupal\groupex_form_cache\Form
 *
 * @ingroup groupex_form_cache
 */
class GroupexFormCacheSettingsForm extends FormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'GroupexFormCache_settings';
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Empty implementation of the abstract submit class.
  }

  /**
   * Defines the settings form for GroupEx Pro Form Cache entities.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   Form definition array.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['GroupexFormCache_settings']['#markup'] = 'Settings form for GroupEx Pro Form Cache entities. Manage field settings here.';
    return $form;
  }

}
