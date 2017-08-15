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
   * TODO Add validation
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $campaignId = $form_state->getValue('campaign_id');
    $membershipID = $form_state->getValue('membership_id');
    if (!is_numeric($membershipID)) {
      $form_state->setErrorByName('membership_id', $this->t('Please, check Membership ID number.'));
      return;
    }

    $memberIDRes = \Drupal::entityQuery('openy_campaign_member')
      ->condition('membership_id', $membershipID) // '12324324'
      ->execute();
    $memberID = reset($memberIDRes);

    // Check MemberCampaign entity
    $memberCampaignRes = \Drupal::entityQuery('openy_campaign_member_campaign')
      ->condition('member', $memberID)
      ->condition('campaign', $campaignId)
      ->execute();

    // If the member is already registered, there will be a basic error message “This member has already registered”.
    if (!empty($memberCampaignRes)) {
      $form_state->setErrorByName('membership_id', $this->t('This member has already registered.'));
      return;
    }

    /** @var Member $member Create Temporary Member object. Will be saved by submit. */
    $member = Member::createMemberFromCRMData($membershipID);
    if ($member instanceof \Drupal\Core\StringTranslation\TranslatableMarkup) {
      $form_state->setErrorByName('membership_id', $member);
      return;
    }

    // Get Campaign entity
    $campaign = Node::load($campaignId);

    // Create MemberCampaign entity
    $memberCampaignValues = [
      'campaign' => $campaign,
      'member' => $member,
    ];
    /** @var MemberCampaign $memberCampaign Create temporary MemberCampaign object. Will be saved by submit. */
    $memberCampaign = \Drupal::entityTypeManager()
      ->getStorage('openy_campaign_member_campaign')
      ->create($memberCampaignValues);

    if (($memberCampaign instanceof MemberCampaign === FALSE) || empty($memberCampaign)) {
      \Drupal::logger('openy_campaign')
        ->error('Error while creating MemberCampaign temporary object.');
      return;
    }

    // Age is in the range from Target Audience Setting from Campaign.
    $validateAge = $memberCampaign->validateMemberAge();
    if (!$validateAge['status']) {
      $errorMessage[] = $validateAge['error'];
    }
    // TODO Uncomment this after all data will be available from CRM API
//    // Member type match Target Audience Setting from Campaign.
//    $validateMemberUnitType = $memberCampaign->validateMemberUnitType();
//    if (!$validateMemberUnitType['status']) {
//      $errorMessage[] = $validateMemberUnitType['error'];
//    }
//    // Branch is one of the selected in the Target Audience Setting from Campaign.
//    $validateMemberBranch = $memberCampaign->validateMemberBranch();
//    if ($validateMemberBranch['status']) {
//      $errorMessage[] = $validateMemberBranch['error'];
//    }
//    // Payment type is of the selected in the Target Audience Setting from Campaign.
//    $validateMemberPaymentType = $memberCampaign->validateMemberPaymentType();
//    if ($validateMemberPaymentType['status']) {
//      $errorMessage[] = $validateMemberPaymentType['error'];
//    }

    // This member is not eligible for the campaign for the following reasons:
    if (!empty($errorMessage)) {
      $errorText = implode('<br>', $errorMessage);
      $form_state->setErrorByName('membership_id',
        $this->t('This member is not eligible for the campaign for the following reasons:<br>@errors', ['@errors' => $errorText]));
      return;
    }

    // Save Member and MemberCampaign entities in storage to save by submit.
    $form_state->setStorage([
      'member' => $member,
      'member_campaign' => $memberCampaign,
    ]);
  }

  /**
   * TODO Add submit
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Get Member and MemberCampaign entities from storage.
    $storage = $form_state->getStorage();

    // If the member has not previously registered, there will be a basic message "This member is now registered".
    drupal_set_message(t('This member is now registered'), 'status', TRUE);
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

}
