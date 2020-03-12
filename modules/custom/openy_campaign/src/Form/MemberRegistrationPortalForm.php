<?php

namespace Drupal\openy_campaign\Form;

use Drupal\node\Entity\Node;
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
use Drupal\openy_campaign\CampaignExtendedRegistrationService;

/**
 * Form controller for the Simplified Team Member Registration Portal form.
 *
 * @ingroup openy_campaign_member
 */
class MemberRegistrationPortalForm extends FormBase {

  const STEP_MEMBER_ID = 'STEP_MEMBER_ID';
  const STEP_EMAIL = 'STEP_EMAIL';
  const STEP_WHERE_ARE_YOU_FROM = 'STEP_WHERE_ARE_YOU_FROM';
  const STEP_WHERE_ARE_YOU_FROM_SPECIFY = 'STEP_WHERE_ARE_YOU_FROM_SPECIFY';

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The node storage.
   *
   * @var \Drupal\node\NodeStorageInterface
   */
  protected $nodeStorage;

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
   * Extended Registration service.
   *
   * @var \Drupal\openy_campaign\CampaignExtendedRegistrationService
   */
  protected $extendedRegistrationService;

  /**
   * Team Member Registration Portal form constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\openy_campaign\RegularUpdater $regular_updater
   *   Regular updater service.
   * @param \Drupal\openy_campaign\CampaignMenuServiceInterface $campaign_menu_service
   *   The Campaign menu service.
   * @param \Drupal\openy_campaign\CampaignScorecardService $campaign_scorecard_service
   *   The Campaign Scorecard service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\openy_campaign\CampaignExtendedRegistrationService
   *   Extended Registration service.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown if the storage handler couldn't be loaded.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager,
    RegularUpdater $regular_updater,
    CampaignMenuServiceInterface $campaign_menu_service,
    CampaignScorecardService $campaign_scorecard_service,
    RouteMatchInterface $route_match,
    CampaignExtendedRegistrationService $extended_registration_service
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->nodeStorage = $entity_type_manager->getStorage('node');
    $this->regularUpdater = $regular_updater;
    $this->campaignMenuService = $campaign_menu_service;
    $this->campaignScorecardService = $campaign_scorecard_service;
    $this->routeMatch = $route_match;
    $this->extendedRegistrationService = $extended_registration_service;
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
      $container->get('current_route_match'),
      $container->get('openy_campaign.extended_registration')
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
    // The block is used by site admins.
    // We shouldn't cache it.
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
        '#markup' => $this->t('There are no active campaigns to register new members.'),
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
    if ($step_value) {
      $step = $step_value;
    }
    elseif (empty($membership_id)) {
      $step = self::STEP_MEMBER_ID;
    }
    else {
      $step = self::STEP_EMAIL;
    }
    $form['step'] = [
      '#type' => 'hidden',
      '#value' => $step,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Continue'),
      '#weight' => 10,
    ];

    if ($step == self::STEP_MEMBER_ID) {
      $currentRoute = $this->routeMatch->getRouteName();
      if ($currentRoute == 'openy_campaign.member-registration-portal') {
        $defaultCampaignID = (!empty($form_state->getValue('campaign_id'))) ? $form_state->getValue('campaign_id') : key($options);
        $defaultCampaign = $this->nodeStorage->load($defaultCampaignID);
        $form['campaign_id'] = [
          '#type' => 'select',
          '#title' => $this->t('Select Campaign'),
          '#options' => $options,
          '#default_value' => $defaultCampaign->id(),
        ];
        if (($defaultCampaign instanceof Node === TRUE) && empty($defaultCampaign->field_campaign_hide_scorecard->value)) {
          $form['#attached']['library'][] = 'openy_campaign/campaign_scorecard';
          $scorecard = $this->campaignScorecardService->generateLiveScorecard($defaultCampaign);
          $form['scorecard'] = [
            '#markup' => '<div id="scorecard-wrapper">' . render($scorecard) . '</div>',
            '#weight' => 100500,
          ];
        }
      }
      else {
        // Select Campaign to assign Member.
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
    }

    if ($step == self::STEP_EMAIL) {
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
        '#weight' => 1,
      ];

      /** @var \Drupal\openy_campaign\Entity\Member $member */
      $member = $form_state->get('member');
      $extended_registration = $form_state->get('extended_registration');
      if ($extended_registration && empty($member->getFullName())) {
        // Force user to provide first and last name.
        $form['first_name'] = [
          '#type' => 'textfield',
          '#title' => $this->t('First Name'),
          '#size' => 60,
          '#maxlength' => 128,
          '#required' => TRUE,
          '#element_required_error' => $this->t('First Name is required.'),
          '#element_validate' => [
            [$this, 'elementValidateRequired'],
          ],
          '#weight' => 2,
        ];
        $form['last_name'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Last Name'),
          '#size' => 60,
          '#maxlength' => 128,
          '#required' => TRUE,
          '#element_required_error' => $this->t('Last Name is required.'),
          '#element_validate' => [
            [$this, 'elementValidateRequired'],
          ],
          '#weight' => 3,
        ];
      }
      if (!$extended_registration) {
        $form['submit']['#value'] = t('Confirm Email and Register');
      }
    }

    $branch = $form_state->get('branch');
    if ($step == self::STEP_WHERE_ARE_YOU_FROM) {
      $category = FALSE;
      $options = $this->extendedRegistrationService->getWhereAreYouFromOptions();
      if ($branch) {
        $category = $branch->field_where_are_you_from_group->entity;
        $category = $category ? $category->id() : $category;
      }
      if ($category && isset($options[$category])) {
        $form_state->set('where_are_you_from', $category);
        $step = self::STEP_WHERE_ARE_YOU_FROM_SPECIFY;
        $form['step']['#value'] = $step;
      }
      else {
        $options_keys = array_keys($options);
        $form['where_are_you_from'] = [
          '#type' => 'select',
          '#title' => t('Choose which Group to participate in'),
          '#options' => $options,
          '#default_value' => reset($options_keys),
          '#required' => TRUE,
        ];
      }
    }

    if ($step == self::STEP_WHERE_ARE_YOU_FROM_SPECIFY) {
      $options = $this->extendedRegistrationService->getWhereAreYouFromSpecifyOptions($form_state->get('where_are_you_from'));
      $options_keys = array_keys($options);
      $form['where_are_you_from_specify'] = [
        '#type' => 'select',
        '#title' => t('Choose which Location to participate in'),
        '#options' => $options,
        '#default_value' => $branch ? "node_{$branch->id()}" : reset($options_keys),
        '#required' => TRUE,
      ];
      $form['submit']['#value'] = t('Register');
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $step = $form_state->getValue('step');

    if ($step == self::STEP_MEMBER_ID) {
      $this->stepOneValidation($form, $form_state);
      return;
    }
    if ($step == self::STEP_EMAIL) {
      $config = $this->config('openy_campaign.general_settings');
      $membershipEmail = $form_state->getValue('membership_email');
      if (empty($membershipEmail)) {
        $msgEmptyMemberEmail = $config->get('error_msg_empty_member_email');
        $msgEmptyMemberEmail = check_markup($msgEmptyMemberEmail['value'], $msgEmptyMemberEmail['format']);
        $form_state->setErrorByName('membership_email', $msgEmptyMemberEmail);
        return;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $step = $form_state->getValue('step');
    $email = $form_state->get('membership_email');

    if ($step == self::STEP_MEMBER_ID) {
      $form_state->setRebuild();
      $form_state->setValue('step', self::STEP_EMAIL);
      $campaign_id = $form_state->getValue('campaign_id');
      $campaign = $this->nodeStorage->load($campaign_id);
      $extended_registration = $campaign->field_campaign_ext_registration->value;
      $form_state->set('extended_registration', $extended_registration);
      return;
    }

    if ($step == self::STEP_EMAIL) {
      $extended_registration = $form_state->get('extended_registration');
      if ($extended_registration) {
        $form_state->setRebuild();
        $form_state->set('membership_email', $form_state->getValue('membership_email'));
        $form_state->set('first_name', $form_state->getValue('first_name'));
        $form_state->set('last_name', $form_state->getValue('last_name'));
        $form_state->setValue('step', self::STEP_WHERE_ARE_YOU_FROM);
      }
      else {
        $email = $form_state->getValue('membership_email');
        $step = self::STEP_WHERE_ARE_YOU_FROM_SPECIFY;
      }
    }

    if ($step == self::STEP_WHERE_ARE_YOU_FROM) {
      // Check if there are second level items for selected "Where are you from".
      // In case if not - skip "Specify" step and go finish registration, using 1st level term.
      $options = $this->extendedRegistrationService->getWhereAreYouFromSpecifyOptions($form_state->getValue('where_are_you_from'));
      if (empty($options)) {
        $step = self::STEP_WHERE_ARE_YOU_FROM_SPECIFY;
        $form_state->setValue('where_are_you_from_specify', "term_{$form_state->getValue('where_are_you_from')}");
      }
      else {
        $form_state->setRebuild();
        $form_state->set('where_are_you_from', $form_state->getValue('where_are_you_from'));
        $form_state->setValue('step', self::STEP_WHERE_ARE_YOU_FROM_SPECIFY);
      }
    }

    if ($step == self::STEP_WHERE_ARE_YOU_FROM_SPECIFY) {
      // Get Member and MemberCampaign entities from storage.
      $storage = $form_state->getStorage();
      $extended_registration = $storage['extended_registration'];

      // Save Member entity.
      if (!empty($storage['member'])) {
        /** @var \Drupal\openy_campaign\Entity\Member $member Member object. */
        $member = $storage['member'];
        // Update email.
        $member->setEmail($email);
        $first_name = $form_state->get('first_name');
        $last_name = $form_state->get('last_name');
        if (!empty($first_name)) {
          $member->setFirstName($first_name);
        }
        if (!empty($last_name)) {
          $member->setLastName($last_name);
        }
        // Update Where Are You From and Specify values.
        if ($extended_registration) {
          $where_are_you_from_specify = explode('_', $form_state->getValue('where_are_you_from_specify'));
          if (reset($where_are_you_from_specify) == 'node') {
            $member->setBranchId(end($where_are_you_from_specify));
          }
          else {
            $member->setWhereAreYouFrom(end($where_are_you_from_specify));
          }
        }
        $member->save();
      }

      // Save MemberCampaign entity.
      if (!empty($storage['member_campaign'])) {
        /** @var \Drupal\openy_campaign\Entity\MemberCampaign $memberCampaign MemberCampaign object. */
        $memberCampaign = $storage['member_campaign'];
        // Define visits goal.
        $memberCampaign->defineGoal();
        // Update Where Are You From and Specify values.
        if ($extended_registration) {
          $where_are_you_from = $form_state->get('where_are_you_from');
          $where_are_you_from_specify = explode('_', $form_state->getValue('where_are_you_from_specify'));
          if (reset($where_are_you_from_specify) == 'node') {
            $memberCampaign->setWhereAreYouFrom($where_are_you_from);
            $memberCampaign->setWhereAreYouFromSpecify(end($where_are_you_from_specify));
          }
          else {
            $memberCampaign->setWhereAreYouFrom(end($where_are_you_from_specify));
          }
        }
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
  }

  /**
   * Step 1 form validations.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  private function stepOneValidation(array &$form, FormStateInterface $form_state) {
    $campaignID = $form_state->getValue('campaign_id');
    $membershipID = $form_state->getValue('membership_id');

    /** @var \Drupal\node\Entity\Node $campaign Current campaign. */
    $campaign = $this->nodeStorage->load($campaignID);
    $extended_registration = $campaign->field_campaign_ext_registration->value;

    $config = $this->config('openy_campaign.general_settings');
    $errorDefault = $config->get('error_msg_default');
    // Get error from Campaign node.
    if (!empty($campaign->field_error_default->value)) {
      $errorDefault = check_markup($campaign->field_error_default->value, $campaign->field_error_default->format);
    }

    // Check MemberCampaign entity.
    $memberCampaignID = MemberCampaign::findMemberCampaign($membershipID, $campaignID);

    // If the member is already registered, there will be a basic error message “This member has already registered”.
    if ($memberCampaignID) {
      $form_state->setErrorByName('membership_id', $this->t('This member has already registered.'));
      return;
    }

    /** @var \Drupal\openy_campaign\Entity\Member $member Load or create Temporary Member entity. Will be saved by submit. */
    $member = Member::loadMemberFromCRMData($membershipID);
    // Membership ID must be valid Personify entry only if extended registration is disabled.
    // Because for enabled extended registration it could be also invite code, not membership ID.
    if (!$extended_registration && (($member instanceof Member === FALSE) || empty($member))) {
      $form_state->setErrorByName('membership_id', $errorDefault);
      return;
    }

    // If member was found in Personify - process they as usual.
    if ($member instanceof Member) {
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

      // If User does not have an assigned branch he is now allowed to register.
      $isEmptyBranch = empty($member->branch->entity);
      if ($isEmptyBranch) {
        $msgAudienceMessages = $config->get('error_msg_target_audience_settings');
        $errorAudience = check_markup(
          $msgAudienceMessages['value'],
          $msgAudienceMessages['format']
        );
        // Get error from Campaign node.
        if (!empty($campaign->field_error_target_audience->value)) {
          $errorAudience = check_markup(
            $campaign->field_error_target_audience->value,
            $campaign->field_error_target_audience->format
          );
        }
        $form_state->setErrorByName('membership_id', $errorAudience);
        return;
      }
      else {
        $form_state->set('branch', $member->branch->entity);
      }

      /** @var \Drupal\openy_campaign\Entity\MemberCampaign $memberCampaign Create temporary MemberCampaign entity. Will be saved by submit. */
      $memberCampaign = MemberCampaign::createMemberCampaign($member, $campaign, 'portal');
      if (($memberCampaign instanceof MemberCampaign === FALSE) || empty($memberCampaign)) {
        $form_state->setErrorByName('membership_id', $errorDefault);
        return;
      }

      // Check Target Audience Settings from Campaign.
      $validateAudienceErrorMessages = $memberCampaign->validateTargetAudienceSettings();
      // Member is ineligible due to the Target Audience Setting.
      if (!empty($validateAudienceErrorMessages)) {
        $errorText = implode('<br>', $validateAudienceErrorMessages);
        $form_state->setErrorByName('membership_id',
          $this->t('This member is not eligible for the campaign for the following reasons:<br>@errors', ['@errors' => $errorText]));
        return;
      }

      $personifyEmail = $member->getPersonifyEmail();
      if (!empty($personifyEmail)) {
        $form_state->setTemporaryValue('personify_email', $personifyEmail);
      }
    }
    else {
      // This was an invite code.
      $member = Member::loadMemberFromInvite($membershipID);

      /** @var \Drupal\openy_campaign\Entity\MemberCampaign $memberCampaign Create temporary MemberCampaign entity. Will be saved by submit. */
      $memberCampaign = MemberCampaign::createMemberCampaign($member, $campaign, 'portal');
      if (($memberCampaign instanceof MemberCampaign === FALSE) || empty($memberCampaign)) {
        $form_state->setErrorByName('membership_id', $errorDefault);
        return;
      }
    }

    // Save Member and MemberCampaign entities in storage to save by submit.
    $form_state->set('member', $member);
    $form_state->set('campaign', $campaign);
    $form_state->set('member_campaign', $memberCampaign);
  }

}
