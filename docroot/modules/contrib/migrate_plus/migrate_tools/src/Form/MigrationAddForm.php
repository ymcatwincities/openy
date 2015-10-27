<?php

/**
 * @file
 * Contains Drupal\migrate_tools\Form\MigrationAddForm.
 */

namespace Drupal\migrate_tools\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Class MigrationAddForm.
 *
 * Provides the add form for our migration entity.
 *
 * @package Drupal\migrate_tools\Form
 *
 * @ingroup migrate_tools
 */
class MigrationAddForm extends MigrationFormBase {

  /**
   * Returns the actions provided by this form.
   *
   * For our add form, we only need to change the text of the submit button.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   An associative array containing the current state of the form.
   *
   * @return array
   *   An array of supported actions for the current entity form.
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    $actions = parent::actions($form, $form_state);
//    $actions['submit']['#value'] = $this->t('Create Migration');
    unset($actions['submit']);
    return $actions;
  }

}
