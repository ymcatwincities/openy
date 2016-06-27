<?php

namespace Drupal\ymca_retention\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\personify_sso\PersonifySso;
use Drupal\ymca_retention\Entity\Member;
use Drupal\ymca_retention\PersonifyApi;

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
    $membership_id = $form_state->getValue('membership_id');
    $query = \Drupal::entityQuery('ymca_retention_member')
      ->condition('mail', $mail)
      ->condition('membership_id', $membership_id);
    $result = $query->execute();
    if (!empty($result)) {
      $form_state->setErrorByName('mail', $this->t('The email address %value with this facility access ID is already registered.', [
        '%value' => $mail,
      ]));
    }

    // Numeric validation.
    if (!is_numeric($membership_id)) {
      $form_state->setErrorByName('membership_id', $this->t('Facility Access ID should be numeric'));
    }
    // Number of digits.
    if (strlen($membership_id) != 10 && strlen($membership_id) != 13) {
      $form_state->setErrorByName('membership_id', $this->t('Facility Access ID should contain either 10 or 12 digits'));
    }
    // If there are some error, then continue and do not do request to Personify.
    if ($form_state->hasAnyErrors()) {
      return;
    }

    $personify_result = PersonifyApi::getPersonifyMemberInformation($membership_id);
    // @todo Here we need to verify results. and check is there an alias, and then search user in db by alias ID.
    if (empty($personify_result) || !empty($personify_result->ErrorMessage)) {
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
    $membership_id = $form_state->getValue('membership_id');

    // @todo setup dates from settings.
    $from = '2016-03-01 00:00:00.000';
    $to = '2016-07-01 00:00:00.000';

    $past_result = PersonifyApi::getPersonifyVisitCountByDate($membership_id, $from, $to);

    // @todo static goal, move to settings.
    $goal = 30;
    if (empty($past_result->ErrorMessage) && $past_result->TotalVisits > 0) {
      // @todo Move to settings.
      $percent = 0.3;
      $goal = $past_result->TotalVisits + ($past_result->TotalVisits * $percent);
    }

    // @todo setup dates from settings.
    $from = '2016-04-26 00:00:00.000';
    $to = '2016-06-23 00:00:00.000';

    $current_result = PersonifyApi::getPersonifyVisitCountByDate($membership_id, $from, $to);
    $total_visits = 0;
    if (empty($current_result->ErrorMessage) && $current_result->TotalVisits > 0) {
      $total_visits = $current_result->TotalVisits;
    }
    // @todo identify employee here.

    /** @var Member $entity */
    $entity = \Drupal::entityTypeManager()
      ->getStorage('ymca_retention_member')
      ->create([
        'membership_id' => $membership_id,
        'mail' => $form_state->getValue('mail'),
        'first_name' => $personify_member->FirstName,
        'last_name' => $personify_member->LastName,
        'branch' => (int) $personify_member->BranchId,
        'is_employee' => FALSE,
        'visit_goal' => $goal,
        'total_visits' => $total_visits,
      ]);
    $entity->save();
    drupal_set_message('Membership ID registered');
    $form_state->setRedirect('ymca_retention.enroll_success_page');
  }

}
