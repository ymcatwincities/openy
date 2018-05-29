<?php

namespace Drupal\openy_system\Form;

use Drupal\system\Form\ModulesListForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides openy modules installation interface.
 */
class OpenyModulesListForm extends ModulesListForm {

  protected $openyPackages = [
    'OpenY',
    'OpenY (Experimental)',
    'YMCA Maryland',
  ];

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    // Remove all packages, that not related to OpenY.
    foreach ($form['modules'] as $package => $modules) {
      if (!in_array($package, $this->openyPackages)) {
        unset($form['modules'][$package]);
      }
    }

    // TODO: Also, before component removing - would be nice to add a step
    // with a list of entities and where they are used ( for paragraps ) to
    // let content managers check all will be good after removal.
    // Just a simple table with a list of view/edit.
    return $form;
  }

}
