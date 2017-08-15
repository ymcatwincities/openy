<?php

namespace Drupal\openy_campaign\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\openy_campaign\Entity\Member;
use Drupal\openy_campaign\Entity\MemberCampaign;

/**
 * Form controller for the Simplified Team Member Registration Portal form.
 *
 * @ingroup openy_campaign_member
 */
class MemberRegistrationPortalForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'openy_campaign_registration_portal_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $campaignIds = \Drupal::entityQuery('node')->condition('type','campaign')->execute();
    $campaigns = Node::loadMultiple($campaignIds);
    $options = [];
    foreach ($campaigns as $item) {
      /** @var Node $item */
      $options[$item->id()] = $item->getTitle();
    }

    // Select Campaign to assign Member
    $form['campaign_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Select Campaign'),
      '#options' => $options,
    ];

    // The id on the membership card.
    $form['membership_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Scan the Membership ID'),
      '#default_value' => '',
      '#size' => 60,
      '#maxlength' => 128,
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Register Team Member'),
    ];

    return $form;
  }

  /**
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

    // If the member has not previously registered, there will be a basic message "This member is now registered".
    drupal_set_message(t('This member is now registered'), 'status', TRUE);
  }

}
