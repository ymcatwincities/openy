<?php

namespace Drupal\openy_campaign\Form;

use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\openy_campaign\Entity\MemberCampaign;

/**
 * Form for the Member Login popup.
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
    $form['#prefix'] = '<div id="modal_openy_campaign_login_form">';
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
    if ($memberCampaignID) {
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
    $msgNotRegistered = $config->get('error_msg_not_registered');
    $errorNotRegistered = check_markup($msgNotRegistered['value'], $msgNotRegistered['format']);
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
