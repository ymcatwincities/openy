<?php

namespace Drupal\openy_campaign\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\node\Entity\Node;
use Drupal\openy_campaign\Entity\Member;
use Drupal\openy_campaign\Entity\MemberCampaign;

/**
 * Form for the Member Registration popup.
 *
 * @ingroup openy_campaign_member
 */
class MemberRegisterForm extends FormBase {

  protected static $containerId = 'modal_openy_campaign_register_form';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'openy_campaign_register_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $campaign_id = NULL) {
    $form['#prefix'] = '<div id="' . static::$containerId . '">';
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

    $membership_id = $form_state->get('membership_id');
    $personify_email = $form_state->getTemporaryValue('personify_email');
    $step_value = $form_state->getValue('step');

    // Determine step of the form - which screen to show.
    // 1 - enter Member ID;
    // 2 - confirm email address from Personify;
    // 3 - manually enter email address.
    if ($step_value) {
      $step = $step_value;
    }
    elseif (empty($membership_id)) {
      $step = 1;
    }
    else {
      if (empty($personify_email)) {
        $step = 3;
      }
      else {
        $step = 2;
      }
    }
    $form['step'] = [
      '#type' => 'hidden',
      '#value' => $step,
    ];

    if ($step == 1) {
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
      // Member ID text.
      $settings = $this->config('openy_campaign.general_settings');
      $msgMemberIdText = $settings->get('register_form_text');
      $memberIdText = check_markup($msgMemberIdText['value'], $msgMemberIdText['format']);
      $form['member_id_text'] = [
        '#theme' => 'openy_campaign_register_text',
        '#text' => $memberIdText,
      ];
    }

    if ($step == 2 || $step == 3) {
      $form['email'] = [
        '#type' => 'email',
        '#title' => $this->t('Email'),
        '#title_display' => 'hidden',
        '#required' => TRUE,
        '#attributes' => [
          'placeholder' => [
            $this->t('Your email'),
          ],
        ],
        '#element_required_error' => $this->t('Email is required.'),
        '#element_validate' => [
          ['\Drupal\Core\Render\Element\Email', 'validateEmail'],
          [$this, 'elementValidateRequired'],
        ],
      ];
      if ($step == 2) {
        $form['email']['#default_value'] = $personify_email;
        $form['email']['#attributes']['disabled'] = TRUE;
      }
    }

    $ajax = [
      'callback' => [$this, 'submitModalFormAjax'],
      'method' => 'replaceWith',
    ];

    $form['submit_ok'] = [
      '#type' => 'submit',
      '#name' => 'submit_ok',
      '#value' => $this->t('Sign in'),
      '#attributes' => [
        'class' => [
          'btn',
          'btn-lg',
          'btn-primary',
          'campaign-blue',
        ],
      ],
      '#ajax' => $ajax,
    ];

    if ($step == 2) {
      $form['submit_change'] = [
        '#type' => 'submit',
        '#name' => 'submit_change',
        '#value' => $this->t('No, change'),
        '#attributes' => [
          'class' => [
            'btn',
            'btn-lg',
            'btn-primary',
            'campaign-grey',
            'pull-right',
          ],
        ],
        '#ajax' => $ajax,
      ];
    }

    if ($step == 2) {
      $form['submit_ok']['#value'] = $this->t('Yes, all fine');
      $form['submit_ok']['#attributes']['class'][] = 'pull-left';
    }
    if ($step == 3) {
      $form['submit_ok']['#value'] = $this->t('OK');
    }

    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $step = $form_state->getValue('step');
    if ($step != 1) {
      $form_state->setValue('step', $step + 1);
      return;
    }

    $campaignID = $form_state->getValue('campaign_id');
    $membershipID = $form_state->getValue('membership_id');

    /** @var \Drupal\node\Entity\Node $campaign */
    $campaign = Node::load($campaignID);

    $config = $this->config('openy_campaign.general_settings');
    $msgDefault = $config->get('error_msg_default');
    $errorDefault = check_markup($msgDefault['value'], $msgDefault['format']);
    // Get error from Campaign node.
    if (!empty($campaign->field_error_default->value)) {
      $errorDefault = check_markup($campaign->field_error_default->value, $campaign->field_error_default->format);
    }

    $msgMembershipId = $config->get('error_msg_membership_id');
    $errorMembershipId = check_markup($msgMembershipId['value'], $msgMembershipId['format']);
    // Get error from Campaign node.
    if (!empty($campaign->field_error_membership_id->value)) {
      $errorMembershipId = check_markup($campaign->field_error_membership_id->value, $campaign->field_error_membership_id->format);
    }

    // TODO Add check length of $membershipID
    // Check correct Membership ID - commented out, because IDs may contain letters.
    /*if (!is_numeric($membershipID)) {
      $form_state->setErrorByName('membership_id', $errorMembershipId);
      return;
    }*/

    // Check MemberCampaign entity.
    $memberCampaignID = MemberCampaign::findMemberCampaign($membershipID, $campaignID);

    // Registration attempt for already registered member.
    // The validation is disabled in order to log in already registered users.
    /*
    if ($memberCampaignID) {
      $msgAlreadyRegistered = $config->get('error_register_already_registered');
      $errorAlreadyRegistered = check_markup($msgAlreadyRegistered['value'], $msgAlreadyRegistered['format']);
      // Get error from Campaign node.
      if (!empty($campaign->field_reg_already_registered->value)) {
        $errorAlreadyRegistered = check_markup($campaign->field_reg_already_registered->value, $campaign->field_reg_already_registered->format);
      }

      $form_state->setErrorByName('membership_id', $errorAlreadyRegistered);
      return;
    }
    */

    /** @var \Drupal\openy_campaign\Entity\Member $member Load or create Temporary Member object. Will be saved by submit. */
    $member = Member::loadMemberFromCRMData($membershipID);
    if (($member instanceof Member === FALSE) || empty($member)) {
      $form_state->setErrorByName('membership_id', $errorMembershipId);

      return;
    }

    // User is inactive if he does not have active order number.
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

    $registrationType = 'site';
    // Check if we are need to output the mobile version.
    if (!empty($_GET['mobile'])) {
      $registrationType = 'mobile';
    }
    /** @var \Drupal\openy_campaign\Entity\MemberCampaign $memberCampaign Create temporary MemberCampaign entity. Will be saved by submit. */
    $memberCampaign = MemberCampaign::createMemberCampaign($member, $campaign, $registrationType);
    if (($memberCampaign instanceof MemberCampaign === FALSE) || empty($memberCampaign)) {
      $form_state->setErrorByName('membership_id', $errorDefault);

      return;
    }

    // Check Target Audience Settings from Campaign.
    $validateAudienceErrorMessages = $memberCampaign->validateTargetAudienceSettings();

    // Member is ineligible due to the Target Audience Setting.
    if (!empty($validateAudienceErrorMessages)) {
      $msgAudienceMessages = $config->get('error_msg_target_audience_settings');
      $msgValue = implode('<br/>', $validateAudienceErrorMessages);
      $errorAudience = check_markup($msgValue . $msgAudienceMessages['value'], $msgAudienceMessages['format']);
      // Get error from Campaign node.
      if (!empty($campaign->field_error_target_audience->value)) {
        $errorAudience = check_markup($msgValue . $campaign->field_error_target_audience->value, $campaign->field_error_target_audience->format);
      }

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

    $personifyEmail = $member->getPersonifyEmail();
    if (!empty($personifyEmail)) {
      $form_state->set('email', $personifyEmail);
      $form_state->setTemporaryValue('personify_email', $personifyEmail);

      $form_state->setValue('step', $step + 1);
    }
    else {
      $form_state->setValue('step', 3);
    }
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

    $step = $form_state->getValue('step');
    $triggering_element = $form_state->getTriggeringElement();
    $email = $form_state->getValue('email');

    $campaignID = $form_state->getValue('campaign_id');
    $membershipID = $form_state->getValue('membership_id');

    // Rebuild form for step 2 and 3.
    if ($step == 2 ||
      ($step == 3 && ($triggering_element['#name'] == 'submit_change' || empty($email)))) {

      // If a member already exists, log them in here.
      if ($memberCampaignID = MemberCampaign::findMemberCampaign($membershipID, $campaignID)) {
        $response = new AjaxResponse();
        $config = $this->config('openy_campaign.general_settings');
        $storage = $form_state->getStorage();

        /** @var \Drupal\node\Entity\Node $campaign Campaign object. */
        $campaign = $storage['campaign'];
        $campaignStartDate = new \DateTime($campaign->get('field_campaign_start_date')->getString());
        // If Campaign is not started.
        if ($campaignStartDate >= new \DateTime()) {
          $msgNotStarted = $config->get('error_register_checkins_not_started');
          $msgNotStarted = check_markup($msgNotStarted['value'], $msgNotStarted['format']);
          // TODO: use hook_theme instead of inline template.
          $wrappedModalMessage = '<div class="message-wrapper">' . $msgNotStarted . '</div>';
          $modalTitle = t('Thank you!');
        }
        else {
          MemberCampaign::login($membershipID, $campaignID);
          $msgSuccess = $config->get('successful_login');
          $modalMessage = check_markup($msgSuccess['value'], $msgSuccess['format']);
          // TODO: use hook_theme instead of inline template.
          $wrappedModalMessage = '<div class="message-wrapper">' . $modalMessage . '</div>';
          $modalTitle = t('Thank you!');
        }
        $modalPopup = [
          '#theme' => 'openy_campaign_popup',
          '#form' => [
            '#markup' => $wrappedModalMessage,
          ]
        ];
        // Add an AJAX command to open a modal dialog with the form as the content.
        $response->addCommand(new OpenModalDialogCommand($modalTitle, $modalPopup, ['width' => '800']));
        $response->addCommand(new InvokeCommand('#openy_campaign_popup', 'closeDialogByClick'));
        // Close dialog and redirect to Campaign main page.
        $response->addCommand(new InvokeCommand('#drupal-modal', 'closeDialog', ['<campaign-front>']));
        return $response;
      }

      // Rebuild form with new $form and $form_state values.
      $new_form = \Drupal::formBuilder()
        ->rebuildForm($this->getFormId(), $form_state, $form);

      // Refreshing form.
      $response->addCommand(new ReplaceCommand('#' . static::$containerId, $new_form));

      return $response;
    }

    // If there are any form errors, re-display the form.
    if ($form_state->hasAnyErrors()) {
      $response->addCommand(new ReplaceCommand('#' . static::$containerId, $form));

      return $response;
    }

    // Registration handler.
    if ($step > 2 && $triggering_element['#name'] == 'submit_ok') {
      $storage = $form_state->getStorage();

      /** @var \Drupal\node\Entity\Node $campaign Campaign object. */
      $campaign = $storage['campaign'];
      $campaignStartDate = new \DateTime($campaign->get('field_campaign_start_date')->getString());
      $campaignEndDate = new \DateTime($campaign->get('field_campaign_end_date')->getString());

      /** @var \Drupal\openy_campaign\Entity\Member $member Member entity. */
      $member = $storage['member'];
      // Update email.
      if (!empty($form_state->getValue('email'))) {
        $member->setEmail($form_state->getValue('email'));
      }
      $member->save();

      /** @var \Drupal\openy_campaign\Entity\MemberCampaign $memberCampaign MemberCampaign entity. */
      $memberCampaign = $storage['member_campaign'];
      // Define visits goal.
      $memberCampaign->defineGoal();
      $memberCampaign->save();

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

      // Get default values from settings.
      $config = $this->config('openy_campaign.general_settings');

      $msgSuccess = $config->get('successful_registration');
      $modalMessage = check_markup($msgSuccess['value'], $msgSuccess['format']);
      // Get message from Campaign node.
      if (!empty($campaign->field_successful_registration->value)) {
        $modalMessage = check_markup($campaign->field_successful_registration->value, $campaign->field_successful_registration->format);
      }

      // If Campaign is not started.
      if ($campaignStartDate >= new \DateTime()) {
        $msgNotStarted = $config->get('error_register_checkins_not_started');
        $modalMessage = check_markup($msgNotStarted['value'], $msgNotStarted['format']);
        // Get message from Campaign node.
        if (!empty($campaign->field_reg_checkins_not_started->value)) {
          $modalMessage = check_markup($campaign->field_reg_checkins_not_started->value, $campaign->field_reg_checkins_not_started->format);
        }
        // TODO: use hook_theme instead of inline template.
        $wrappedModalMessage = '<div class="message-wrapper">' . $modalMessage . '</div>';
        $response->addCommand(new ReplaceCommand('#' . static::$containerId, $wrappedModalMessage));
        $response->addCommand(new InvokeCommand('#drupal-modal', 'closeDialogByClick'));
      }
      else {
        // Log in user instead of showing the registration success message.
        MemberCampaign::login($memberCampaign->getMember()->getMemberId(), $memberCampaign->getCampaign()->id());
        $msgSuccess = $config->get('successful_login');
        $modalMessage = check_markup($msgSuccess['value'], $msgSuccess['format']);
        // TODO: use hook_theme instead of inline template.
        $wrappedModalMessage = '<div class="message-wrapper">' . $modalMessage . '</div>';
        $modalTitle = t('Thank you!');

        $modalPopup = [
          '#theme' => 'openy_campaign_popup',
          '#form' => [
            '#markup' => $wrappedModalMessage,
          ]
        ];
        // Add an AJAX command to open a modal dialog with the form as the content.
        $response->addCommand(new OpenModalDialogCommand($modalTitle, $modalPopup, ['width' => '800']));
        $response->addCommand(new InvokeCommand('#openy_campaign_popup', 'closeDialogByClick'));
        // Close dialog and redirect to Campaign main page.
        $response->addCommand(new InvokeCommand('#drupal-modal', 'closeDialog', ['<campaign-front>']));
      }


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
    $campaignStartDate = new \DateTime($campaign->get('field_campaign_start_date')->getString());
    $campaignEndDate = new \DateTime($campaign->get('field_campaign_end_date')->getString());
    $currentDate = new \DateTime();

    return $currentDate >= $campaignStartDate && $currentDate <= $campaignEndDate;
  }

}
