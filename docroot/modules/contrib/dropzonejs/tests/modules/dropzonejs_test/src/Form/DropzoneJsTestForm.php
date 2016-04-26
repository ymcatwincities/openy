<?php
namespace Drupal\dropzonejs_test\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class DropzoneJsTestForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return '_dropzonejs_test_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['dropzonejs'] = [
      '#title' => t('DropzoneJs element'),
      '#type' => 'dropzonejs',
      '#required' => TRUE,
      '#dropzone_description' => 'DropzoneJs description',
      '#max_filesize' => '1M',
      '#extensions' => 'jpg png',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
