<?php

namespace Drupal\optimizely\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

use Drupal\optimizely\Util\AccountId;

/**
 * Implements the form for Account Info.
 */
class AccountSettingsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'optimizely_account_info';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $settings_form['#theme'] = 'optimizely_account_settings_form';
    $form['#attached']['library'][] = 'optimizely/optimizely.forms';

    $settings_form['optimizely_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Optimizely ID Number'),
      '#default_value' => AccountId::getId(),
      '#description' =>
      $this->t('Your Optimizely account ID. This is the number after "/js/" in the
           Optimizely Tracking Code found in your account on the Optimizely website.'),
      '#size' => 60,
      '#maxlength' => 256,
      '#required' => TRUE,
    ];
    $settings_form['actions'] = ['#type' => 'actions'];
    $settings_form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => 'Submit',
    ];

    // Will be $form in the render array and the template file.
    return $settings_form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $oid = $form_state->getValue('optimizely_id');
    if (!preg_match('/^\d+$/', $oid)) {
      $form_state->setErrorByName('optimizely_id',
                                  $this->t('Your Optimizely ID should be numeric.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    // Store the optimizely account id number.
    $optimizely_id = $form_state->getValue('optimizely_id');
    AccountId::setId($optimizely_id);

    // Update the default project / experiment entry with the account ID value.
    \Drupal::database()->update('optimizely')
      ->fields([
        'project_code' => $optimizely_id,
      ])
      ->condition('oid', '1')
      ->execute();

    // Inform the administrator that the default project / experiment entry
    // is ready to be enabled.
    drupal_set_message(t('The default project entry is now ready to be enabled.
       This will apply the default Optimizely project tests sitewide.'), 'status');

    // Redirect back to projects listing.
    $form_state->setRedirect('optimizely.listing');
  }

}
