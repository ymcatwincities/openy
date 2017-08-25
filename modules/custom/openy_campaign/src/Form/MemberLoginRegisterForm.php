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

    $validate_required = [get_class($this), 'elementValidateRequired'];
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
      '#element_validate' => [
        $validate_required,
      ],
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
    $errorDefault = $config->get('error_msg_default');

    $campaignID = $form_state->getValue('campaign_id');
    $membershipID = $form_state->getValue('membership_id');

    // TODO Add check length of $membershipID
    // Check correct Membership ID
    if (!is_numeric($membershipID)) {
      $form_state->setErrorByName('membership_id', $config->get('error_msg_membership_id'));

      return;
    }

    // Check MemberCampaign entity
    $memberCampaignID = MemberCampaign::findMemberCampaign($membershipID, $campaignID);

    $campaign = Node::load($campaignID);
    $isCheckinsPeriod = $this->checkCheckinsPeriod($campaign);
    // If the member is already registered previously, but the campaign challenges have not yet started.
    if ($memberCampaignID) {
      if ($isCheckinsPeriod) {
        // Login member - set SESSION
        MemberCampaign::login($membershipID, $campaignID);
        $form_state->setStorage([
          'loggedin' => TRUE,
        ]);
      } else {
        $form_state->setErrorByName('membership_id', $config->get('error_msg_checkins_not_started'));
      }

      return;
    }

    /** @var Member $member Load or create Temporary Member object. Will be saved by submit. */
    $member = Member::loadMemberFromCRMData($membershipID);
    if (($member instanceof Member === FALSE) || empty($member)) {
      $form_state->setErrorByName('membership_id', $errorDefault);

      return;
    }

    // TODO Check from CRM API if a member shows as inactive
    $isInactiveMember = FALSE;
    if ($isInactiveMember) {
      $form_state->setErrorByName('membership_id', $config->get('error_msg_member_is_inactive'));
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
      $errorMessages = implode(' - ', $validateAudienceErrorMessages);
      $form_state->setErrorByName('membership_id', $errorMessages . $config->get('error_msg_target_audience_settings'));

      return;
    }

    // Save Member and MemberCampaign entities in storage to save by submit.
    $form_state->setStorage([
      'loggedin' => FALSE,
      'member' => $member,
      'campaign' => $campaign,
      'member_campaign' => $memberCampaign,
    ]);
  }


  /**
   * AJAX callback handler that displays any errors or a success message.
   */
  public function submitModalFormAjax(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    // Get member action
    $values = $form_state->getValues();
    $action = (!empty($values['member_action'])) ? $values['member_action'] : 'login';
    $campaign_id = $values['campaign_id'];

    // If there are any form errors, re-display the form.
    if ($form_state->hasAnyErrors()) {
      $response->addCommand(new ReplaceCommand('#modal_openy_campaign_login_register_form', $form));

      return $response;
    }

    // Submit handler.

    // Get status info and entities from storage.
    $storage = $form_state->getStorage();

    // For existed and logged in during validation members
    if ($storage['loggedin'] === TRUE) {
      $response->addCommand(new OpenModalDialogCommand($this->t('Thank you!'), $this->t('Thank you for logging in.'), ['width' => 800]));

      // Set redirect to Campaign page
      $fullPath = \Drupal::request()->getSchemeAndHttpHost() . '/node/' . $campaign_id;
      $response->addCommand(new RedirectCommand($fullPath));

      return $response;
    }

    // Save Member entity.
    if (!empty($storage['member'])) {
      /** @var Member $member Member object. */
      $member = $storage['member'];
      $member->save();
    }

    // Save MemberCampaign entity.
    if (!empty($storage['member_campaign'])) {
      /** @var MemberCampaign $memberCampaign MemberCampaign object. */
      $memberCampaign = $storage['member_campaign'];
      // define visits goal
      $memberCampaign->defineGoal();

      $memberCampaign->save();
    }

    // For just registered members
    /** @var Node $campaign Campaign object. */
    $campaign = $storage['campaign'];
    $checkinsOpenDate = new \DateTime($campaign->get('field_goal_check_ins_start_date')->getString());

    // Set message depends on member action
    $messageBeforeCheckins = t('Thank you for registering, you are all set! Be sure to check back on @date when the challenge starts!',
      ['@date' => $checkinsOpenDate->format('F j')]);
    $messageCheckins = t('Thank you for registering, you are all set and logged in!');
    if ($action == 'login') {
      $messageBeforeCheckins = t('Challenge is not started yet. Be sure to check back on @date when the challenge starts!',
        ['@date' => $checkinsOpenDate->format('F j')]);
      $messageCheckins = t('Thank you for logging in.');
    }

    $modalTitle = $this->t('Thank you!');
    // If a member ID is successfully registered during the registration phase, but before checking start.
    if ($checkinsOpenDate >= new \DateTime()) {
      $response->addCommand(new OpenModalDialogCommand($modalTitle, $messageBeforeCheckins, ['width' => 800]));
    }

    // If Campaign already in active phase - login member
    $isCheckinsPeriod = $this->checkCheckinsPeriod($campaign);
    if ($isCheckinsPeriod) {
      // Login member
      $membershipID = $values['membership_id'];
      MemberCampaign::login($membershipID, $campaign->id());

      $response->addCommand(new OpenModalDialogCommand($modalTitle, $messageCheckins, ['width' => 800]));
    }

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
  protected function checkCheckinsPeriod(Node $campaign) {
    /** @var Node $campaign Campaign node. */
    $checkinsOpenDate = new \DateTime($campaign->get('field_campaign_start_date')->getString());
    $checkinsCloseDate = new \DateTime($campaign->get('field_campaign_end_date')->getString());
    $currentDate = new \DateTime();

    return $currentDate >= $checkinsOpenDate && $currentDate <= $checkinsCloseDate;
  }

}
