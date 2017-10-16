<?php

namespace Drupal\custom_formatters;

use Drupal\Core\Form\FormStateInterface;

/**
 * Interface FormatterInterface.
 */
interface FormatterExtrasInterface {

  /**
   * Settings form callback.
   *
   * @return array
   *   The form API array.
   */
  public function settingsForm();

  /**
   * Save callback for settings form.
   *
   * @param array $form
   *   The submmitted formatter form.
   * @param FormStateInterface $form_state
   *   The submitted formatter form state object.
   */
  public function settingsSave(array $form, FormStateInterface $form_state);

}
