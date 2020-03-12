<?php

namespace Drupal\openy_campaign\Form;

use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\openy_campaign\Entity\Member;
use Drupal\openy_campaign\Entity\MemberCampaign;
use Drupal\openy_campaign\OpenYLocaleDate;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for the Member Login popup.
 *
 * @ingroup openy_campaign_member
 */
class MemberLoginForm extends FormBase {

  /**
   * The node storage.
   *
   * @var \Drupal\node\NodeStorageInterface
   */
  protected $nodeStorage;

  /**
   * MemberLoginForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown if the storage handler couldn't be loaded.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->nodeStorage = $entity_type_manager->getStorage('node');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'openy_campaign_login_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $campaign_id = NULL, $landing_page_id = NULL) {
    $form['#prefix'] = '<div id="modal_openy_campaign_login_form">';
    $form['#suffix'] = '</div>';

    // The status messages that will contain any form errors.
    $form['status_messages'] = [
      '#type' => 'status_messages',
      '#weight' => -10,
    ];

    // Set Campaign ID from URL.
    $form['campaign_id'] = [
      '#type' => 'hidden',
      '#value' => $campaign_id,
    ];

    // Set Landing page ID to change URL.
    $form['landing_page_id'] = [
      '#type' => 'hidden',
      '#value' => $landing_page_id,
    ];

    /** @var \Drupal\node\Entity\Node $campaign */
    $campaign = $this->nodeStorage->load($campaign_id);
    $extended_registration = $campaign->field_campaign_ext_registration->value;
    $form['extended_registration'] = [
      '#type' => 'hidden',
      '#value' => $extended_registration,
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
      '#element_validate' => [
        [$this, 'elementValidateRequired'],
      ],
    ];
    if ($extended_registration) {
      $form['membership_id']['#attributes']['placeholder'] = $this->t('Your member ID / Invite code');
      $form['membership_id']['#element_required_error'] = $this->t('Member ID / Invite code is required.');
    }
    // Member ID text.
    $settings = $this->config('openy_campaign.general_settings');
    $msgMemberIdText = $settings->get('register_form_text');
    $memberIdText = check_markup($msgMemberIdText['value'], $msgMemberIdText['format']);
    $form['member_id_text'] = [
      '#theme' => 'openy_campaign_register_text',
      '#text' => $memberIdText,
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

    $campaignID = $form_state->getValue('campaign_id');
    $membershipID = $form_state->getValue('membership_id');
    $extended_registration = $form_state->getValue('extended_registration');

    /** @var \Drupal\node\Entity\Node $campaign Current campaign. */
    $campaign = $this->nodeStorage->load($campaignID);

    // Don't allow inactive members to login.
    $member = Member::loadMemberFromCRMData($membershipID);
    if (!$extended_registration || ($member instanceof Member)) {
      $isInactiveMember = empty($member->order_number->value);
      if ($isInactiveMember) {
        $msgMemberInactive = $config->get('error_msg_member_is_inactive');
        $errorMemberInactive = check_markup($msgMemberInactive['value'], $msgMemberInactive['format']);
        // Get error from Campaign node.
        if (!empty($campaign->field_error_member_is_inactive->value)) {
          $errorMemberInactive = check_markup($campaign->field_error_member_is_inactive->value, $campaign->field_error_member_is_inactive->format);
        }

        $form_state->setErrorByName('membership_id', $errorMemberInactive);
        return;
      }
    }

    $msgMembershipId = $config->get('error_msg_membership_id');
    $errorMembershipId = check_markup($msgMembershipId['value'], $msgMembershipId['format']);
    // Get error from Campaign node.
    if (!empty($campaign->field_error_membership_id->value)) {
      $errorMembershipId = check_markup($campaign->field_error_membership_id->value, $campaign->field_error_membership_id->format);
    }

    // Check MemberCampaign entity.
    $memberCampaignID = MemberCampaign::findMemberCampaign($membershipID, $campaignID);

    $isCampaignPeriod = $this->checkCampaignPeriod($campaign);

    // If the member is already registered previously.
    if ($memberCampaignID) {
      if ($isCampaignPeriod) {
        // Login member - set SESSION.
        MemberCampaign::login($membershipID, $campaignID);
        $form_state->setStorage([
          'status' => 'loggedin',
          'campaign' => $campaign,
        ]);
      }
      else {
        $msgNotStarted = $config->get('error_login_checkins_not_started');
        $errorNotStarted = check_markup($msgNotStarted['value'], $msgNotStarted['format']);
        // Get error from Campaign node.
        if (!empty($campaign->field_login_checkins_not_started->value)) {
          $errorNotStarted = check_markup($campaign->field_login_checkins_not_started->value, $campaign->field_login_checkins_not_started->format);
        }

        $form_state->setErrorByName('membership_id', $errorNotStarted);
      }

      return;
    }

    // For not registered users.
    $msgNotRegistered = $config->get('error_login_not_registered');
    $errorNotRegistered = check_markup($msgNotRegistered['value'], $msgNotRegistered['format']);
    // Get error from Campaign node.
    if (!empty($campaign->field_login_not_registered->value)) {
      $errorNotRegistered = check_markup($campaign->field_login_not_registered->value, $campaign->field_login_not_registered->format);
    }

    $form_state->setErrorByName('membership_id', $errorNotRegistered);
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

    // If there are any form errors, re-display the form.
    if ($form_state->hasAnyErrors()) {
      $response->addCommand(new ReplaceCommand('#modal_openy_campaign_login_form', $form));

      return $response;
    }

    $storage = $form_state->getStorage();

    // For existed and logged in during validation members. All other cases were checked during validation.
    if ($storage['status'] === 'loggedin') {
      /** @var \Drupal\node\Entity\Node $campaign Campaign object. */
      $campaign = $storage['campaign'];

      // Get default values from settings.
      $config = $this->config('openy_campaign.general_settings');

      $msgSuccess = $config->get('successful_login');
      $modalMessage = check_markup($msgSuccess['value'], $msgSuccess['format']);
      // Get message from Campaign node.
      if (!empty($campaign->field_successful_login->value)) {
        $modalMessage = check_markup($campaign->field_successful_login->value, $campaign->field_successful_login->format);
      }

      // Login before Campaign start.
      $localeCampaignStartDate = OpenYLocaleDate::createDateFromFormat($campaign->get('field_campaign_start_date')->getString());
      if (!$localeCampaignStartDate->dateExpired()) {
        $msgNotStarted = $config->get('error_login_checkins_not_started');
        $modalMessage = check_markup($msgNotStarted['value'], $msgNotStarted['format']);
        // Get message from Campaign node.
        if (!empty($campaign->field_login_checkins_not_started->value)) {
          $modalMessage = check_markup($campaign->field_login_checkins_not_started->value, $campaign->field_login_checkins_not_started->format);
        }
      }

      $landingPageId = $form_state->getValue('landing_page_id');
      $queryParameters = [];
      if (!empty($landingPageId)) {
        $landingPage = $this->nodeStorage->load($landingPageId);
        $queryParameters = [trim($landingPage->toUrl()->toString(), "/")];
      }

      $response->addCommand(new ReplaceCommand('#modal_openy_campaign_login_form', $modalMessage));

      $response->addCommand(new InvokeCommand('#drupal-modal', 'closeDialog', $queryParameters));

      return $response;
    }

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
   * @param $campaign
   *   Node Campaign node
   *
   * @return bool
   */
  protected function checkCampaignPeriod(Node $campaign) {
    /** @var \Drupal\node\Entity\Node $campaign Campaign node. */
    $localeCampaignStartDate = OpenYLocaleDate::createDateFromFormat($campaign->get('field_campaign_start_date')->getString());
    $localeCampaignEndDate = OpenYLocaleDate::createDateFromFormat($campaign->get('field_campaign_end_date')->getString());

    return $localeCampaignStartDate->dateExpired() && !$localeCampaignEndDate->dateExpired();
  }

}
