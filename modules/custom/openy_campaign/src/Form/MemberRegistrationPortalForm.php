<?php

namespace Drupal\openy_campaign\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\openy_campaign\CampaignMenuServiceInterface;
use Drupal\openy_campaign\CampaignScorecardService;
use Drupal\openy_campaign\Entity\Member;
use Drupal\openy_campaign\Entity\MemberCampaign;
use Drupal\openy_campaign\RegularUpdater;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the Simplified Team Member Registration Portal form.
 *
 * @ingroup openy_campaign_member
 */
class MemberRegistrationPortalForm extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Regular updater service.
   *
   * @var \Drupal\openy_campaign\RegularUpdater
   */
  protected $regularUpdater;

  /**
   * The Campaign menu service.
   *
   * @var \Drupal\openy_campaign\CampaignMenuServiceInterface
   */
  protected $campaignMenuService;

  /**
   * The Campaign Scorecard service.
   *
   * @var \Drupal\openy_campaign\CampaignScorecardService
   */
  protected $campaignScorecardService;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Team Member Registration Portal form constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\openy_campaign\RegularUpdater $regular_updater
   *   Regular updater service.
   * @param CampaignMenuServiceInterface $campaign_menu_service
   *   The Campaign menu service.
   * @param CampaignScorecardService $campaign_scorecard_service
   *   The Campaign Scorecard service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, RegularUpdater $regular_updater,
                              CampaignMenuServiceInterface $campaign_menu_service,
                              CampaignScorecardService $campaign_scorecard_service,
                              RouteMatchInterface $route_match) {
    $this->entityTypeManager = $entity_type_manager;
    $this->regularUpdater = $regular_updater;
    $this->campaignMenuService = $campaign_menu_service;
    $this->campaignScorecardService = $campaign_scorecard_service;
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('openy_campaign.regular_updater'),
      $container->get('openy_campaign.campaign_menu_handler'),
      $container->get('openy_campaign.generate_campaign_scorecard'),
      $container->get('current_route_match')
    );
  }

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
    $form['#cache'] = ['max-age' => 0];

    $form['#prefix'] = '<div class="container">';
    $form['#suffix'] = '</div>';

    $form['link'] = [
      '#title' => $this->t('Members List >>>'),
      '#type' => 'link',
      '#url' => Url::fromRoute('openy_campaign.team_member.list'),
      '#weight' => 0,
      '#attributes' => [
        'class' => [
          'align-right',
        ],
      ],
      '#prefix' => '<div class="row">',
      '#suffix' => '</div>',
    ];

    $campaigns = $this->campaignMenuService->getActiveCampaigns();
    if (empty($campaigns)) {
      $form['empty'] = [
        '#markup' => $this->t('There is no active campaigns.'),
      ];

      return $form;
    }

    $options = [];
    foreach ($campaigns as $item) {
      /** @var \Drupal\node\Entity\Node $item */
      $options[$item->id()] = $item->getTitle();
    }

    $membership_id = $form_state->get('membership_id');
    $personify_email = $form_state->getTemporaryValue('personify_email');
    $step_value = $form_state->getValue('step');

    // Determine step of the form - which screen to show.
    // 1 - select Campaign and enter Member ID;
    // 2 - confirm email address from Personify or change it. Register member;
    if ($step_value) {
      $step = $step_value;
    }
    elseif (empty($membership_id)) {
      $step = 1;
    }
    else {
      $step = 2;
    }
    $form['step'] = [
      '#type' => 'hidden',
      '#value' => $step,
    ];

    if ($step == 1) {
      $currentRoute = $this->routeMatch->getRouteName();
      if ($currentRoute == 'openy_campaign.member-registration-portal') {
        $defaultCampaignID = (!empty($form_state->getValue('campaign_id'))) ? $form_state->getValue('campaign_id') : key($options);
        $defaultCampaign = $this->entityTypeManager->getStorage('node')->load($defaultCampaignID);

        if ($defaultCampaign instanceof \Drupal\node\Entity\Node === TRUE) {
          $form['#attached']['library'][] = 'openy_campaign/campaign_scorecard';
          $form['campaign_id'] = [
            '#type' => 'select',
            '#title' => $this->t('Select Campaign'),
            '#options' => $options,
            '#default_value' => $defaultCampaign->id(),
          ];
          $scorecard = $this->campaignScorecardService->generateLiveScorecard($defaultCampaign);

          $form['scorecard'] = [
            '#markup' => '<div id="scorecard-wrapper">' . render($scorecard) . '</div>',
            '#weight' => 100500,
          ];
        }
      }
      else {
        // Select Campaign to assign Member
        $form['campaign_id'] = [
          '#type' => 'select',
          '#title' => $this->t('Select Campaign'),
          '#options' => $options,
        ];
      }
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
        '#value' => t('Continue'),
      ];
    } else {
      // The members email address.
      $form['membership_email'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Check Membership Email Address'),
        '#default_value' => !empty($personify_email) ? $personify_email : '',
        '#size' => 60,
        '#maxlength' => 128,
        '#required' => TRUE,
        '#element_required_error' => $this->t('Email is required.'),
        '#element_validate' => [
          ['\Drupal\Core\Render\Element\Email', 'validateEmail'],
          [$this, 'elementValidateRequired'],
        ],
      ];

      $form['submit'] = [
        '#type' => 'submit',
        '#value' => t('Confirm Email and Register'),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $step = $form_state->getValue('step');

    if ($step == 1) {
      $this->stepOneValidation($form, $form_state);
      $form_state->setRebuild(TRUE);
    }
    elseif ($step == 2) {
      $config = $this->config('openy_campaign.general_settings');
      $storage = $form_state->getStorage();

      /** @var Member $member Member object. */
      $member = $storage['member'];
      $campaign = $storage['campaign'];
      $memberCampaign = $storage['member_campaign'];

      $membershipEmail = $form_state->getValue('membership_email');
      if (empty($membershipEmail)) {
        $msgEmptyMemberEmail = $config->get('error_msg_empty_member_email');
        $msgEmptyMemberEmail = check_markup($msgEmptyMemberEmail['value'], $msgEmptyMemberEmail['format']);
        $form_state->setErrorByName('membership_email', $msgEmptyMemberEmail);
        return;
      }
      $member->setEmail($membershipEmail);
      $member->save();

      // Save Member and MemberCampaign entities in storage to save by submit.
      $form_state->setStorage([
        'member' => $member,
        'campaign' => $campaign,
        'member_campaign' => $memberCampaign,
        'membership_email' => $membershipEmail,
      ]);
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($step = $form_state->getValue('step') != 2) {
      return;
    }

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
      // define visits goal
      $memberCampaign->defineGoal();

      $memberCampaign->save();
    }

    /** @var \Drupal\node\Entity\Node $campaign Campaign object. */
    $campaign = $storage['campaign'];
    $campaignStartDate = new \DateTime($campaign->get('field_campaign_start_date')->getString());
    $campaignEndDate = new \DateTime($campaign->get('field_campaign_end_date')->getString());

    // Get visits history from CRM for the past Campaign dates - from Campaign start to yesterday date.
    $dateFrom = $campaignStartDate->setTime(0, 0, 0);
    $dateTo = new \DateTime();
    $dateTo->sub(new \DateInterval('P1D'))->setTime(23, 59, 59);
    $membersData[] = [
      'member_id' => $member->getId(),
      'master_customer_id' => $member->getPersonifyId(),
      'start_date' => $dateFrom,
      'end_date' => $campaignEndDate,
    ];
    $this->regularUpdater->createQueue($dateFrom, $dateTo, $membersData);

    // If the member has not previously registered, there will be a basic message "This member is now registered".
    drupal_set_message(t('This member is now registered'), 'status', TRUE);
  }

  /**
   * Step 1 form validations
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  private function stepOneValidation(array &$form, FormStateInterface $form_state) {
    $campaignID = $form_state->getValue('campaign_id');
    $membershipID = $form_state->getValue('membership_id');

    /** @var \Drupal\node\Entity\Node $campaign Current campaign. */
    $campaign = $this->entityTypeManager->getStorage('node')->load($campaignID);

    $config = $this->config('openy_campaign.general_settings');
    $errorDefault = $config->get('error_msg_default');
    // Get error from Campaign node
    if (!empty($campaign->field_error_default->value)) {
      $errorDefault = check_markup($campaign->field_error_default->value, $campaign->field_error_default->format);
    }

    // Check MemberCampaign entity
    $memberCampaignID = MemberCampaign::findMemberCampaign($membershipID, $campaignID);

    // If the member is already registered, there will be a basic error message “This member has already registered”.
    if ($memberCampaignID) {
      $form_state->setErrorByName('membership_id', $this->t('This member has already registered.'));
      return;
    }

    /** @var Member $member Load or create Temporary Member entity. Will be saved by submit. */
    $member = Member::loadMemberFromCRMData($membershipID);
    if (($member instanceof Member === FALSE) || empty($member)) {
      $form_state->setErrorByName('membership_id', $errorDefault);

      return;
    }

    $isInactiveMember = empty($member->order_number->value);
    if ($isInactiveMember) {
      $msgMemberInactive = $config->get('error_msg_member_is_inactive');
      $errorMemberInactive = check_markup($msgMemberInactive['value'], $msgMemberInactive['format']);
      // Get error from Campaign node
      if (!empty($campaign->field_error_member_is_inactive->value)) {
        $errorMemberInactive = check_markup($campaign->field_error_member_is_inactive->value, $campaign->field_error_member_is_inactive->format);
      }

      $form_state->setErrorByName('membership_id', $errorMemberInactive);
      return;
    }

    /** @var MemberCampaign $memberCampaign Create temporary MemberCampaign entity. Will be saved by submit. */
    $memberCampaign = MemberCampaign::createMemberCampaign($member, $campaign, 'portal');
    if (($memberCampaign instanceof MemberCampaign === FALSE) || empty($memberCampaign)) {
      $form_state->setErrorByName('membership_id', $errorDefault);

      return;
    }

    // Check Target Audience Settings from Campaign.
    $validateAudienceErrorMessages = $memberCampaign->validateTargetAudienceSettings();

    // Member is ineligible due to the Target Audience Setting
    if (!empty($validateAudienceErrorMessages)) {
      $errorText = implode('<br>', $validateAudienceErrorMessages);
      $form_state->setErrorByName('membership_id',
        $this->t('This member is not eligible for the campaign for the following reasons:<br>@errors', ['@errors' => $errorText]));

      return;
    }

    // Save Member and MemberCampaign entities in storage to save by submit.
    $form_state->setStorage([
      'member' => $member,
      'campaign' => $campaign,
      'member_campaign' => $memberCampaign,
    ]);

    $personifyEmail = $member->getPersonifyEmail();
    if (!empty($personifyEmail)) {
      $form_state->setTemporaryValue('personify_email', $personifyEmail);
    }

    $form_state->setValue('step', 2);
  }

}
