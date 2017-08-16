<?php

namespace Drupal\openy_campaign\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\openy_campaign\Entity\Member;
use Drupal\openy_campaign\Entity\MemberCampaign;

/**
 * Form controller for the Member Login popup form.
 *
 * @ingroup openy_campaign_member
 */
class MemberLoginForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'openy_campaign_login_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $campaign_id = NULL) {
    // Set Campaign ID from URL
    $form['campaign_id'] = [
      '#type' => 'hidden',
      '#value' => $campaign_id,
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
      '#value' => t('OK'),
    ];

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
    $isMemberCampaignExists = MemberCampaign::isMemberCampaignExists($membershipID, $campaignID);

    $campaign = Node::load($campaignID);
    $isCheckinsPeriod = $this->checkCheckinsPeriod($campaign);
    // If the member is already registered previously, but the campaign challenges have not yet started.
    if ($isMemberCampaignExists) {
      if ($isCheckinsPeriod) {
        // TODO Login member - set cookie
        drupal_set_message(t('Thank you for logging in.'), 'status', TRUE);
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
      $form_state->setErrorByName('membership_id', $config->get('error_msg_target_audience_settings'));

      return;
    }

    // Save Member and MemberCampaign entities in storage to save by submit.
    $form_state->setStorage([
      'member' => $member,
      'campaign' => $campaign,
      'member_campaign' => $memberCampaign,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get Member and MemberCampaign entities from storage.
    $storage = $form_state->getStorage();

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
      $memberCampaign->save();
    }

    // For just registered members
    if (!empty($storage['campaign'])) {
      /** @var Node $campaign Campaign object. */
      $campaign = $storage['campaign'];
      $isCheckinsPeriod = $this->checkCheckinsPeriod($campaign);
      if ($isCheckinsPeriod) {
        // TODO Login member
        drupal_set_message(t('Thank you for logging in.'), 'status', TRUE);
      }
    }
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
    $checkinsOpenDate = new \DateTime($campaign->get('field_check_ins_start_date')->getString());
    $checkinsCloseDate = new \DateTime($campaign->get('field_check_ins_end_date')->getString());
    $currentDate = new \DateTime();

    return $currentDate >= $checkinsOpenDate && $currentDate <= $checkinsCloseDate;
  }

}
