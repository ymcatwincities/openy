<?php

namespace Drupal\purge_queuer_test\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\purge_ui\Form\QueuerConfigFormBase;

/**
 * Queuer with a configuration form.
 *
 * @see \Drupal\purge_queuer_test\Plugin\Purge\Queuer\WithFormQueuer.
 */
class QueuerConfigForm extends QueuerConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'purge_queuer_test.configform';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['textfield'] = [
      '#type' => 'textfield',
      '#title' => t('Test'),
      '#required' => FALSE,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitFormSuccess(array &$form, FormStateInterface $form_state) {
    // Nothing to do here.
  }

}
