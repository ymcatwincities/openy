<?php

namespace Drupal\ymca_personify_upgrade\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Run form.
 */
class YMCARunForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ymca_personify_upgrade_run_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Run Personify Links Upgrade'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    \Drupal::service('ymca_personify_upgrade')->run();
  }

}
