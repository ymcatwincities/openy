<?php

namespace Drupal\openy_campaign\Form;

use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\openy_campaign\Entity\Member;
use Drupal\openy_campaign\Entity\MemberCampaign;

/**
 * Form for the Member Login/Registration popup.
 *
 * @ingroup openy_campaign_member
 */
class MemberLoginRegisterForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'openy_campaign_login_register_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $action = 'login', $campaign_id = NULL) {
    $form['#prefix'] = '<div id="modal_openy_campaign_login_register_form">';
    $form['#suffix'] = '</div>';

    // The status messages that will contain any form errors.
    $form['status_messages'] = [
      '#type' => 'status_messages',
      '#weight' => -10,
    ];

    // Set Campaign ID from URL
    $form['campaign_id'] = [
      '#type' => 'hidden',
      '#value' => $campaign_id,
    ];
    // Set member action from URL
    $form['member_action'] = [
      '#type' => 'hidden',
      '#value' => $action,
    ];

    // The id on the membership card.
    $form['membership_id'] = [
      '#type' => 'textfield',
      '#size' => 60,
      '#maxlength' => 128,
      '#required' => TRUE,
      '#attributes' => [
        'placeholder' => [
          $this->t('Your member ID'),
        ],
      ],
      '#element_required_error' => $this->t('Member ID is required.'),
      '#element_validate' => [$this, 'elementValidateRequired'],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('OK'),
      '#attributes' => [
        'class' => [
          'use-ajax',
        ],
      ],
      '#ajax' => [
        'callback' => [$this, 'submitModalFormAjax'],
        'event' => 'click',
      ],
    ];

    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('openy_campaign.general_settings');
    $msgDefault = $config->get('error_msg_default');
    $errorDefault = check_markup($msgDefault['value'], $msgDefault['format']);

    $campaignID = $form_state->getValue('campaign_id');
    $membershipID = $form_state->getValue('membership_id');
    $action = $form_state->getValue('member_action');

    // TODO Add check length of $membershipID
    // Check correct Membership ID
    if (!is_numeric($membershipID)) {
      $msgMembershipId = $config->get('error_msg_membership_id');
      $errorMembershipId = check_markup($msgMembershipId['value'], $msgMembershipId['format']);
      $form_state->setErrorByName('membership_id', $errorMembershipId);

      return;
    }

    // Check MemberCampaign entity
    $memberCampaignID = MemberCampaign::findMemberCampaign($membershipID, $campaignID);

    /** @var Node $campaign */
    $campaign = Node::load($campaignID);
    $isCampaignPeriod = $this->checkCampaignPeriod($campaign);

    // If the member is already registered previously.
    if ($action == 'login' && $memberCampaignID) {
      if ($isCampaignPeriod) {
        // Login member - set SESSION
        MemberCampaign::login($membershipID, $campaignID);
        $form_state->setStorage([
          'status' => 'loggedin',
          'campaign' => $campaign,
        ]);
      } else {
        $msgNotStarted = $config->get('error_msg_checkins_not_started');
        $errorNotStarted = check_markup($msgNotStarted['value'], $msgNotStarted['format']);
        $form_state->setErrorByName('membership_id', $errorNotStarted);
      }

      return;
    }

    // For not registered users.
    if ($action == 'login' && !$memberCampaignID) {
      $msgNotRegistered = $config->get('error_msg_not_registered');
      $errorNotRegistered = check_markup($msgNotRegistered['value'], $msgNotRegistered['format']);
      $form_state->setErrorByName('membership_id', $errorNotRegistered);

      return;
    }

    // Registration attempt for already registered member.
    if ($action == 'registration' && $memberCampaignID) {
      $msgAlreadyRegistered = $config->get('error_msg_already_registered');
      $errorAlreadyRegistered = check_markup($msgAlreadyRegistered['value'], $msgAlreadyRegistered['format']);
      $form_state->setErrorByName('membership_id', $errorAlreadyRegistered);

      return;
    }

    // Register logic: $action == 'register' && !$memberCampaignID

    /** @var Member $member Load or create Temporary Member object. Will be saved by submit. */
    $member = Member::loadMemberFromCRMData($membershipID);
    if (($member instanceof Member === FALSE) || empty($member)) {
      $form_state->setErrorByName('membership_id', $errorDefault);

      return;
    }

    // TODO Check from CRM API if a member shows as inactive
    $isInactiveMember = FALSE;
    if ($isInactiveMember) {
      $msgMemberInactive = $config->get('error_msg_member_is_inactive');
      $errorMemberInactive = check_markup($msgMemberInactive['value'], $msgMemberInactive['format']);
      $form_state->setErrorByName('membership_id', $errorMemberInactive);
    }

    /** @var MemberCampaign $memberCampaign Create temporary MemberCampaign entity. Will be saved by submit. */
    $memberCampaign = MemberCampaign::createMemberCampaign($member, $campaign);
    if (($memberCampaign instanceof MemberCampaign === FALSE) || empty($memberCampaign)) {
      $form_state->setErrorByName('membership_id', $errorDefault);

      return;
    }

    // Check Target Audience Settings from Campaign.
    $validateAudienceErrorMessages = $memberCampaign->validateTargetAudienceSettings();

    // Member is ineligible due to the Target Audience Setting
    if (!empty($validateAudienceErrorMessages)) {
      $msgAudienceMessages = $config->get('error_msg_target_audience_settings');
      $msgValue = implode(' - ', $validateAudienceErrorMessages) . $msgAudienceMessages['value'];
      $errorAudience = check_markup($msgValue, $msgAudienceMessages['format']);
      $form_state->setErrorByName('membership_id', $errorAudience);

      return;
    }

    // Save Member and MemberCampaign entities in storage to save by submit.
    $form_state->setStorage([
      'status' => FALSE,
      'member' => $member,
      'campaign' => $campaign,
      'member_campaign' => $memberCampaign,
    ]);
  }

  /**
   * AJAX callback handler that displays any errors or a success message.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function submitModalFormAjax(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    // Get member action
    $values = $form_state->getValues();
    $action = (!empty($values['member_action'])) ? $values['member_action'] : 'login';

    // If there are any form errors, re-display the form.
    if ($form_state->hasAnyErrors()) {
      $response->addCommand(new ReplaceCommand('#modal_openy_campaign_login_register_form', $form));

      return $response;
    }

    // Login handler.
    if ($action == 'login') {
      $response = $this->submitLoginForm($form, $form_state);
    }
    // Registration handler.
    else {
      $response = $this->submitRegistrationForm($form, $form_state);
    }

    return $response;
  }

  /**
   * Submit handler for Login.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  private function submitLoginForm(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $storage = $form_state->getStorage();

    // For existed and logged in during validation members. All other cases were checked during validation.
    if ($storage['status'] === 'loggedin') {
      $modalTitle = $this->t('Thank you!');
      $modalMessage = t('Thank you for logging in.');

      // Login before Campaign start
      /** @var Node $campaign Campaign object. */
      $campaign = $storage['campaign'];
      $campaignStartDate = new \DateTime($campaign->get('field_campaign_start_date')->getString());

      if ($campaignStartDate > new \DateTime()) {
        $modalMessage = $this->t('Challenge is not started yet. Be sure to check back on @date when the challenge starts!',
          ['@date' => $campaignStartDate->format('F j')]);
      }

      $response->addCommand(new OpenModalDialogCommand(
        $modalTitle,
        $modalMessage,
        ['width' => 800]
      ));

      // Set redirect to Campaign page
      $fullPath = \Drupal::request()->getSchemeAndHttpHost() . '/node/' . $campaign->id();
      $response->addCommand(new RedirectCommand($fullPath));

      return $response;
    }

    return $response;
  }

  /**
   * Submit handler for registration.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  private function submitRegistrationForm(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $storage = $form_state->getStorage();

    /** @var Member $member Save Member entity. */
    $member = $storage['member'];
    $member->save();

    /** @var MemberCampaign $memberCampaign Save MemberCampaign entity. */
    $memberCampaign = $storage['member_campaign'];
    // Define visits goal
    $memberCampaign->defineGoal();
    $memberCampaign->save();

    /** @var Node $campaign Campaign object. */
    $campaign = $storage['campaign'];

    $campaignStartDate = new \DateTime($campaign->get('field_campaign_start_date')->getString());
    $campaignEndDate = new \DateTime($campaign->get('field_campaign_end_date')->getString());

    // Get visits history from CRM for the past Campaign dates.
    /** @var \Drupal\openy_campaign\RegularUpdater $regularUpdater */
    $regularUpdater = \Drupal::service('openy_campaign.regular_updater');

    // Get visits history from Campaign start to yesterday date.
    $dateFrom = $campaignStartDate->setTime(0, 0, 0);
    $dateTo = new \DateTime();
    $dateTo->sub(new \DateInterval('P1D'))->setTime(23, 59, 59);
    $membersData[] = [
      'member_id' => $member->getId(),
      'master_customer_id' => $member->getPersonifyId(),
      'start_date' => $dateFrom,
      'end_date' => $campaignEndDate,
    ];
    $regularUpdater->createQueue($dateFrom, $dateTo, $membersData);

    $modalTitle = $this->t('Thank you!');
    $modalMessage = t('Thank you for registering. Now you can sign in!');
    // If Campaign is not started
    if ($campaignStartDate >= new \DateTime()) {
      $modalMessage = t('Thank you for registering. Be sure to check back on @date when the challenge starts!',
        ['@date' => $campaignStartDate->format('F j')]);
    }

    $response->addCommand(new OpenModalDialogCommand($modalTitle, $modalMessage, ['width' => 800]));

    // Set redirect to Campaign page
    $fullPath = \Drupal::request()->getSchemeAndHttpHost() . '/node/' . $campaign->id();
    $response->addCommand(new RedirectCommand($fullPath));

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
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
   * Check if now is Checkings period of Campaign.
   *
   * @param $campaign Node Campaign node
   *
   * @return bool
   */
  protected function checkCampaignPeriod(Node $campaign) {
    /** @var Node $campaign Campaign node. */
    $campaignStartDate = new \DateTime($campaign->get('field_campaign_start_date')->getString());
    $campaignEndDate = new \DateTime($campaign->get('field_campaign_end_date')->getString());
    $currentDate = new \DateTime();

    return $currentDate >= $campaignStartDate && $currentDate <= $campaignEndDate;
  }

}
