<?php

namespace Drupal\ymca_retention\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ymca_retention\AnonymousCookieStorage;
use Drupal\ymca_retention\Entity\Member;
use Drupal\ymca_retention\PersonifyApi;

/**
 * Member registration form.
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
      '#attributes' => [
        'class' => [
          'blue-medium',
        ],
      ],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $membership_id = $form_state->getValue('membership_id');
    $query = \Drupal::entityQuery('ymca_retention_member')
      ->condition('membership_id', $membership_id);
    $result = $query->execute();
    if (!empty($result)) {
      $form_state->setErrorByName('mail', $this->t('The facility access ID is already registered. Please sign in.'));
      return;
    }

    // Numeric validation.
    if (!is_numeric($membership_id)) {
      $form_state->setErrorByName('membership_id', $this->t('Facility Access ID should be numeric'));
      return;
    }

    // Get information about member from Personify and validate entered membership ID.
    $personify_result = PersonifyApi::getPersonifyMemberInformation($membership_id);
    // @todo Here we need to verify results. and check is there an alias, and then search user in db by alias ID.
    if (empty($personify_result) || !empty($personify_result->ErrorMessage)) {
      $form_state->setErrorByName('membership_id', $this->t('Member with this facility access ID not found, please verify your facility access ID.'));
    }
    else {
      $form_state->setTemporaryValue('personify_member', $personify_result);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get form values.
    $personify_member = $form_state->getTemporaryValue('personify_member');
    $membership_id = $form_state->getValue('membership_id');

    // Get retention settings.
    $settings = \Drupal::config('ymca_retention.general_settings');

    // Get information about number of checkins before campaign.
    $from = $settings->get('date_checkins_start');
    $to = $settings->get('date_checkins_end');
    $past_result = PersonifyApi::getPersonifyVisitCountByDate($membership_id, $from, $to);

    // Calculate a goal for a member.
    $goal = $settings->get('default_goal_number');
    if (empty($past_result->ErrorMessage) && $past_result->TotalVisits > 0) {
      $percent = $settings->get('goal_percentage');
      $calculated_goal = ceil($past_result->TotalVisits + ($past_result->TotalVisits * $percent));
      $goal = min($calculated_goal, $goal);
    }

    // Get information about number of checkins in period of campaign.
    $from = $settings->get('date_registration_open');
    $to = $settings->get('date_registration_close');
    $current_result = PersonifyApi::getPersonifyVisitCountByDate($membership_id, $from, $to);

    $total_visits = 0;
    if (empty($current_result->ErrorMessage) && $current_result->TotalVisits > 0) {
      $total_visits = $current_result->TotalVisits;
    }
    // Identify is user an employee or not.
    $is_employee = !empty($personify_member->ProductCode) && strpos($personify_member->ProductCode, 'STAFF');

    // Create a new entity.
    /** @var Member $entity */
    $entity = \Drupal::entityTypeManager()
      ->getStorage('ymca_retention_member')
      ->create([
        'membership_id' => $membership_id,
        'mail' => $form_state->getValue('mail'),
        'first_name' => $personify_member->FirstName,
        'last_name' => $personify_member->LastName,
        'branch' => (int) $personify_member->BranchId,
        'is_employee' => $is_employee,
        'visit_goal' => $goal,
        'total_visits' => $total_visits,
        'created_by_staff' => FALSE,
      ]);
    $entity->save();

    AnonymousCookieStorage::set('ymca_retention_member', $entity->getId());

    // Redirect to confirmation page.
    $form_state->setRedirect('page_manager.page_view_ymca_retention_pages', ['string' => 'enroll-success']);
  }

}
