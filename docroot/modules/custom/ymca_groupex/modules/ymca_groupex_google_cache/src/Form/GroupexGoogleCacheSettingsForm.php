<?php

namespace Drupal\ymca_groupex_google_cache\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class GroupexGoogleCacheSettingsForm.
 *
 * @package Drupal\ymca_groupex_google_cache\Form
 *
 * @ingroup ymca_groupex_google_cache
 */
class GroupexGoogleCacheSettingsForm extends FormBase {

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'GroupexGoogleCache_settings';
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
   * Defines the settings form for Groupex Google Cache entities.
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
    $form['GroupexGoogleCache_settings']['#markup'] = 'Settings form for Groupex Google Cache entities. Manage field settings here.';
    return $form;
  }

}
