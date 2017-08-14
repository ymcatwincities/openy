<?php

namespace Drupal\openy_campaign\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;

/**
 * Form controller for the Simplified Team Member Registration Portal form.
 *
 * @ingroup openy_campaign_member
 */
class MemberRegistrationForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'openy_campaign_registration_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $destination = '') {
    $campaignIds = \Drupal::entityQuery('node')->condition('type','campaign')->execute();
    $campaigns = Node::loadMultiple($campaignIds);
    $options = [];
    foreach ($campaigns as $item) {
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

    $memberIDRes = \Drupal::entityQuery('openy_campaign_member')
      ->condition('membership_id', $membershipID) // '12324324'
      ->execute();
    $memberID = reset($memberIDRes);

    // If the member is already registered, there will be a basic error message “This member has already registered”.
    if ($this->isMemberRegistered($memberID, $campaignId)) {
      $form_state->setErrorByName('membership_id', $this->t('This member has already registered.'));
    }

    // Get Member entity
    $memberStorage = \Drupal::entityTypeManager()
      ->getStorage('openy_campaign_member');
    /* @var $member \Drupal\openy_campaign\Entity\Member */
    $member = $memberStorage->load($memberID);
    // Get Campaign entity
    $campaign = Node::load($campaignId);

    // Age is in the range from Target Audience Setting from Campaign.
    if ($this->checkMemberAge($member, $campaign) !== TRUE) {
      $errorMessage[] = $this->checkMemberAge($member, $campaign);
    }
    // Member type match Target Audience Setting from Campaign.
    if ($this->checkMemberType($member, $campaign) !== TRUE) {
      $errorMessage[] = $this->checkMemberType($member, $campaign);
    }
    // Branch is one of the selected in the Target Audience Setting from Campaign.
    if ($this->checkMemberBranch($member, $campaign) !== TRUE) {
      $errorMessage[] = $this->checkMemberBranch($member, $campaign);
    }
    // Payment type is of the selected in the Target Audience Setting from Campaign.
    if ($this->checkMemberPaymentType($member, $campaign) !== TRUE) {
      $errorMessage[] = $this->checkMemberPaymentType($member, $campaign);
    }

    // This member is not eligible for the campaign for the following reasons:
    if (!empty($errorMessage)) {
      $errorText = implode('<br>', $errorMessage);
      $form_state->setErrorByName('membership_id',
        $this->t('This member is not eligible for the campaign for the following reasons:<br>@errors', ['@errors' => $errorText]));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Save MemberCampaign entity.


    // If the member has not previously registered, there will be a basic message "This member is now registered".
    drupal_set_message(t('This member is now registered'), 'status', TRUE);
  }

  // @TODO Merge with MemberCampaign entity

  /**
   * Check if the member already registered to Campaign.
   *
   * @param $memberID int Member entity ID
   * @param $campaignId int Campaign entity ID
   *
   * @return bool
   */
  protected function isMemberRegistered($memberID, $campaignId) {
    $memberCampaignRes = \Drupal::entityQuery('openy_campaign_member_campaign')
      ->condition('member_id', $memberID)
      ->condition('campaign_id', $campaignId)
      ->execute();

    return !empty($memberCampaignRes);
  }

  /**
   * Check if the member age fit to the Campaign age range.
   *
   * @param $member object Member entity
   * @param $campaign object Campaign entity
   *
   * @return bool | string TRUE or Error message
   */
  protected function checkMemberAge($member, $campaign) {
    $minAge = $campaign->get('field_campaign_age_minimum')->value;
    $maxAge = $campaign->get('field_campaign_age_maximum')->value;

    $birthday = \DateTime::createFromFormat('Y-m-d', $member->getBirthDate());
    $now = new \DateTime();
    $interval = $now->diff($birthday)->format('%y');

    return ($interval >= $minAge && $interval <= $maxAge) ? TRUE :
      $this->t('Age is not between @min and @max', ['@min' => $minAge, '@max' => $maxAge]);
  }

  /**
   * Check if the member type fit to the Campaign selected types.
   *
   * @param $member object Member entity
   * @param $campaign object Campaign entity
   *
   * @return bool | string TRUE or Error message
   */
  protected function checkMemberType($member, $campaign) {
    $memberTypes = [];

    return (1) ? TRUE :
      $this->t('Member type does not match @types', ['@types' => implode(',', $memberTypes)]);
  }

  /**
   * Check if the member branch fit to the Campaign selected branches.
   *
   * @param $member object Member entity
   * @param $campaign object Campaign entity
   *
   * @return bool | string TRUE or Error message
   */
  protected function checkMemberBranch($member, $campaign) {

    return (1) ? TRUE :
      $this->t('Branch is not included');
  }

  /**
   * Check if the member age fit to the Campaign age range.
   *
   * @param $member object Member entity
   * @param $campaign object Campaign entity
   *
   * @return bool | string TRUE or Error message
   */
  protected function checkMemberPaymentType($member, $campaign) {
    $paymentTypes = [];

    return (1) ? TRUE :
      $this->t('Payment type does not match @types', ['@types' => implode(',', $paymentTypes)]);
  }

}
