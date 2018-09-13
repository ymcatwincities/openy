<?php

namespace Drupal\openy_system\Form;

use Drupal\system\Form\ModulesUninstallConfirmForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\openy\Form\ConfigureProfileForm;

/**
 * Builds a confirmation form to uninstall selected Open Y project packages.
 */
class OpenyModulesUninstallConfirmForm extends ModulesUninstallConfirmForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['#attached']['library'][] = 'openy_system/openy_system';
    $form['actions']['submit']['#id'] = 'packages-uninstall-confirm';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    // Set redirect to project uninstall page
    $form_state->setRedirect('openy_system.modules_uninstall');
  }

}
