<?php

namespace Drupal\ymca_retention\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ymca_retention\Entity\Member;

/**
 * Code Iframe form.
 */
class MemberRegisterForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ymca_retention_register_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['mail'] = [
      '#type' => 'email',
      '#required' => TRUE,
      '#attributes' => [
        'placeholder' => [
          $this->t('Your e-mail'),
        ],
      ],
    ];
    $form['membership_id'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#attributes' => [
        'placeholder' => [
          $this->t('Your facility access ID'),
        ],
      ],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Register'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $mail = $form_state->getValue('mail');
    $query = \Drupal::entityQuery('ymca_retention_member')
      ->condition('mail', $mail);
    $result = $query->execute();
    if (!empty($result)) {
      $form_state->setErrorByName('mail', $this->t('The email address %value is already registered.', ['%value' => $mail]));
    }

    $membership_id = $form_state->getValue('membership_id');
    // Numeric validation.
    if (!is_numeric($membership_id)) {
      $form_state->setErrorByName('membership_id', $this->t('Facility Access ID should be numeric'));
    }
    // Number of digits.
    if (strlen($membership_id) != 10 && strlen($membership_id) != 12) {
      $form_state->setErrorByName('membership_id', $this->t('Facility Access ID should contain either 10 or 12 digits'));
    }
    // If there are some error, then continue and do not do request to Personify.
    if ($form_state->hasAnyErrors()) {
      return;
    }

    // @todo request to Personify and validating.
    $personify_result = array(
      '$id' => '1',
      "InternalKey" => NULL,
      "NavigationKey" => NULL,
      "Success" => TRUE,
      "ErrorMessage" => '',
      "MasterCustomerId" => "2052780108",
      "SubCustomerId" => 0,
      "FaclilityCardNumber" => "8888999777888",
      "LastName" => "James",
      "FirstName" => "Carole",
      "BranchId" => "36",
      "operationResult" => NULL,
    );
    if (!empty($personify_result['ErrorMessage'])) {
      $form_state->setErrorByName('membership_id', $this->t('Member with this facility access ID did not found, please verify your facility access ID.'));
    }
    else {
      $form_state->setTemporaryValue('personify_member', $personify_result);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $personify_member = $form_state->getTemporaryValue('personify_member');
    // @todo get number checkins.
    /** @var Member $entity */
    $entity = \Drupal::entityTypeManager()
      ->getStorage('ymca_retention_member')
      ->create([
        'membership_id' => $form_state->getValue('membership_id'),
        'mail' => $form_state->getValue('mail'),
        'first_name' => $personify_member['FirstName'],
        'last_name' => $personify_member['LastName'],
        'branch' => (int) $personify_member['BranchId'],
      ]);
    $entity->save();
    drupal_set_message('Membership ID registered');
    $form_state->setRedirect('ymca_retention.enroll_success_page');
  }
}
