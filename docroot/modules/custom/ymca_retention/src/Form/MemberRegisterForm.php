<?php

namespace Drupal\ymca_retention\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
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
    $validate = [get_class($this), 'elementValidateRequired'];
    $form['mail'] = [
      '#type' => 'email',
      '#required' => TRUE,
      '#attributes' => [
        'placeholder' => [
          $this->t('Your e-mail'),
        ],
      ],
      '#element_required_error' => $this->t('Email is required.'),
      '#element_validate' => [
        ['\Drupal\Core\Render\Element\Email', 'validateEmail'],
        $validate,
      ],
    ];
    $form['membership_id'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#attributes' => [
        'placeholder' => [
          $this->t('Your facility access ID'),
        ],
        'class' => [
          'facility-access-id',
        ],
      ],
      '#element_required_error' => $this->t('Facility access ID is required.'),
      '#element_validate' => [$validate],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Register'),
      '#attributes' => [
        'class' => [
          'btn',
          'btn-lg',
          'btn-primary',
          'blue-medium',
        ],
      ],
      '#ajax' => [
        'callback' => [$this, 'ajaxFormCallback'],
        'method' => 'replaceWith',
        'wrapper' => 'registration .registration-form form',
        'progress' => [
          'type' => 'throbber',
          'message' => NULL,
        ],
      ],
    ];

    return $form;
  }

  /**
   * Set a custom validation error on the #required element.
   *
   * @param array $element
   *   Form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   */
  public function elementValidateRequired(array $element, FormStateInterface $form_state) {
    if (!empty($element['#required_but_empty']) && isset($element['#element_required_error'])) {
      $form_state->setError($element, $element['#element_required_error']);
    }
  }

  /**
   * Ajax form callback for displaying errors or redirecting.
   *
   * @param array $form
   *   Form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse|array
   *   Ajax response.
   */
  public function ajaxFormCallback(array &$form, FormStateInterface $form_state) {
    if ($form_state->hasAnyErrors()) {
      $form['messages'] = ['#type' => 'status_messages'];
      return $form;
    }
    else {
      // Instantiate an AjaxResponse Object to return.
      $ajax_response = new AjaxResponse();
      $ajax_response->addCommand(new RedirectCommand(Url::fromRoute('page_manager.page_view_ymca_retention_pages', ['string' => 'enroll-success'])
        ->toString()));
      return $ajax_response;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Get retention settings.
    $settings = \Drupal::config('ymca_retention.general_settings');
    $open_date = new \DateTime($settings->get('date_registration_open'));
    $close_date = new \DateTime($settings->get('date_registration_close'));
    $current_date = new \DateTime();
    // Before be
    if ($current_date < $open_date) {
      $form_state->setErrorByName('form', $this->t('Registration begins %date when the Y Games open.', [
        '%date' => $open_date->format('F j'),
      ]));
      return;
    }
    if ($current_date > $close_date) {
      $form_state->setErrorByName('form', $this->t('The Y Games are now closed and registration is no longer able to be tracked.'));
      return;
    }
    $membership_id = trim($form_state->getValue('membership_id'));
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
    if (empty($personify_result) || !empty($personify_result->ErrorMessage) || empty($personify_result->BranchId) || (int) $personify_result->BranchId == 0) {
      $form_state->setErrorByName('membership_id', $this->t('Sorry, we can\'t locate this facility access ID. Please call 612-230-9622 or stop by your local Y if you need assistance.'));
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

    $query = \Drupal::entityQuery('ymca_retention_member')
      ->condition('personify_id', $personify_member->MasterCustomerId);
    $result = $query->execute();

    if (empty($result)) {
      $entity = $this->createEntity($form_state);
    }
    else {
      $entity = $this->updateEntity(key($result), $form_state);
    }
    AnonymousCookieStorage::set('ymca_retention_member', $entity->getId());

    // Redirect to confirmation page.
    $form_state->setRedirect('page_manager.page_view_ymca_retention_pages', ['string' => 'enroll-success']);
  }

  /**
   * Create member entity.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return \Drupal\ymca_retention\Entity\Member
   *   Member entity.
   */
  protected function createEntity(FormStateInterface $form_state) {
    // Get form values.
    $personify_member = $form_state->getTemporaryValue('personify_member');
    $membership_id = trim($form_state->getValue('membership_id'));

    // Get retention settings.
    $settings = \Drupal::config('ymca_retention.general_settings');

    // Get information about number of checkins before campaign.
    $current_date = new \DateTime();
    $from_date = new \DateTime($settings->get('date_checkins_start'));
    $to_date = new \DateTime($settings->get('date_checkins_end'));

    $to = $to_date->format('m/d/Y g:i A');
    $number_weeks = ceil($from_date->diff($to_date)->days / 7);
    if ($to_date > $current_date) {
      $to = $current_date->format('m/d/Y g:i A');
      $number_weeks = ceil($from_date->diff($current_date)->days / 7);
    }
    $from = $from_date->format('m/d/Y g:i A');
    $past_result = PersonifyApi::getPersonifyVisitCountByDate($membership_id, $from, $to);

    // Get first visit date.
    try {
      $first_visit = new \DateTime($past_result->FirstVisitDate);
    }
    catch (\Exception $e) {
      $first_visit = $from;
    }
    // If user registered after From date, then recalculate number of weeks.
    if ($first_visit > $from_date) {
      $number_weeks = ceil($first_visit->diff($current_date)->days / 7);
    }

    // Calculate a goal for a member.
    $goal = (int) $settings->get('new_member_goal_number');
    if (empty($past_result->ErrorMessage) && $past_result->TotalVisits > 0) {
      $limit_goal = $settings->get('limit_goal_number');
      $calculated_goal = ceil((($past_result->TotalVisits / $number_weeks) * 2) + 1);
      $goal = min(max($goal, $calculated_goal), $limit_goal);
    }
    // Visit goal for late members.
    $close_date = new \DateTime($settings->get('date_campaign_close'));
    $count_days = $current_date->diff($close_date)->days;
    // Set 1 if current date is a date when campaign will be closed.
    $count_days = max(1, $count_days);
    $goal = min($goal, $count_days);

    // Get information about number of checkins in period of campaign.
    $from = $settings->get('date_reporting_open');
    $to = $settings->get('date_reporting_close');
    $current_result = PersonifyApi::getPersonifyVisitCountByDate($membership_id, $from, $to);

    $total_visits = 0;
    if (empty($current_result->ErrorMessage) && $current_result->TotalVisits > 0) {
      $total_visits = $current_result->TotalVisits;
    }
    // Identify is user an employee or not.
    $is_employee = !empty($personify_member->ProductCode) && strpos($personify_member->ProductCode, 'STAFF');

    // @todo This is a bad solution with this condition, if we will reuse this form in the future.
    $route = \Drupal::service('current_route_match')->getRouteName();
    $created_by_staff = $route === 'page_manager.page_view_ymca_retention_pages_y_games_team';

    // Create a new entity.
    /** @var Member $entity */
    $entity = \Drupal::entityTypeManager()
      ->getStorage('ymca_retention_member')
      ->create([
        'membership_id' => $membership_id,
        'personify_id' => $personify_member->MasterCustomerId,
        'mail' => $form_state->getValue('mail'),
        'first_name' => $personify_member->FirstName,
        'last_name' => $personify_member->LastName,
        'branch' => (int) $personify_member->BranchId,
        'is_employee' => $is_employee,
        'visit_goal' => $goal,
        'total_visits' => $total_visits,
        'created_by_staff' => $created_by_staff,
      ]);
    $entity->save();

    return $entity;
  }

  /**
   * Update member entity.
   *
   * @param int $entity_id
   *   Entity id.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return \Drupal\ymca_retention\Entity\Member
   *   Member entity.
   */
  protected function updateEntity($entity_id, FormStateInterface $form_state) {
    $entity = Member::load($entity_id);

    // Update member email.
    $entity->setEmail($form_state->getValue('mail'));
    $entity->save();

    return $entity;
  }

}
