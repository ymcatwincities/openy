<?php

namespace Drupal\openy_campaign\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\node\Entity\Node;
use Drupal\openy_campaign\CampaignExtendedRegistrationService;
use Drupal\openy_campaign\Entity\Member;
use Drupal\openy_campaign\Entity\MemberCampaign;
use Drupal\openy_campaign\RegularUpdater;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form for the Member Registration popup.
 *
 * @ingroup openy_campaign_member
 */
class MemberRegisterForm extends FormBase {

  protected static $containerId = 'modal_openy_campaign_register_form';

  const STEP_MEMBER_ID = 'STEP_MEMBER_ID';
  const STEP_CONFIRM_EMAIL = 'STEP_CONFIRM_EMAIL';
  const STEP_MANUAL_EMAIL = 'STEP_MANUAL_EMAIL';
  const STEP_WHERE_ARE_YOU_FROM = 'STEP_WHERE_ARE_YOU_FROM';
  const STEP_WHERE_ARE_YOU_FROM_SPECIFY = 'STEP_WHERE_ARE_YOU_FROM_SPECIFY';

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * @var \Drupal\openy_campaign\RegularUpdater
   */
  protected $regularUpdater;

  /**
   * The node storage.
   *
   * @var \Drupal\node\NodeStorageInterface
   */
  protected $nodeStorage;

  /**
   * The taxonomy storage.
   *
   * @var \Drupal\taxonomy\TermStorageInterface
   */
  protected $taxonomyStorage;

  /**
   * Extended Registration service.
   *
   * @var \Drupal\openy_campaign\CampaignExtendedRegistrationService
   */
  protected $extendedRegistrationService;

  /**
   * MemberRegisterForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Form\FormBuilderInterface $formBuilder
   * @param \Drupal\openy_campaign\RegularUpdater $regularUpdater
   * @param \Drupal\openy_campaign\CampaignExtendedRegistrationService
   *   Extended Registration service.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown if the storage handler couldn't be loaded.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    FormBuilderInterface $formBuilder,
    RegularUpdater $regularUpdater,
    CampaignExtendedRegistrationService $extended_registration_service
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->formBuilder = $formBuilder;
    $this->regularUpdater = $regularUpdater;
    $this->extendedRegistrationService = $extended_registration_service;
    $this->nodeStorage = $entity_type_manager->getStorage('node');
    $this->taxonomyStorage = $entity_type_manager->getStorage('taxonomy_term');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('form_builder'),
      $container->get('openy_campaign.regular_updater'),
      $container->get('openy_campaign.extended_registration')
    );
  }

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

    /** @var \Drupal\node\Entity\Node $campaign */
    $campaign = $this->nodeStorage->load($campaign_id);
    $extended_registration = $campaign->field_campaign_ext_registration->value;
    $form['extended_registration'] = [
      '#type' => 'hidden',
      '#value' => $extended_registration,
    ];

    $membership_id = $form_state->get('membership_id');
    $personify_email = $form_state->getTemporaryValue('personify_email');
    $step_value = $form_state->getValue('step');

    // Determine step of the form - which screen to show.
    // STEP_MEMBER_ID - enter Member ID.
    // STEP_CONFIRM_EMAIL - confirm email address from Personify.
    // STEP_MANUAL_EMAIL - manually enter email address.
    // STEP_WHERE_ARE_YOU_FROM - Upon completing initial registration user will be asked Where are you from?
    // STEP_WHERE_ARE_YOU_FROM_SPECIFY - specify selection for Dr. Offices, schools, branches or other items from previous step.
    if ($step_value) {
      $step = $step_value;
    }
    elseif (empty($membership_id)) {
      $step = self::STEP_MEMBER_ID;
    }
    elseif (!empty($personify_email)) {
      $step = self::STEP_CONFIRM_EMAIL;
    }
    else {
      $step = self::STEP_MANUAL_EMAIL;
    }

    // Common elements.
    $ajax = [
      'callback' => [$this, 'submitModalFormAjax'],
      'method' => 'replaceWith',
    ];
    $form['step'] = [
      '#type' => 'hidden',
      '#value' => $step,
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
      '#weight' => 10,
    ];

    if ($step == self::STEP_MEMBER_ID) {
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
      if ($extended_registration) {
        $form['membership_id']['#attributes']['placeholder'] = $this->t('Your member ID / Invite code');
        $form['membership_id']['#element_required_error'] = $this->t('Member ID / Invite code is required.');
      }
    }

    if ($step == self::STEP_CONFIRM_EMAIL || $step == self::STEP_MANUAL_EMAIL) {
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
        '#weight' => 1,
      ];
      if ($step == self::STEP_CONFIRM_EMAIL) {
        $form['email']['#default_value'] = $personify_email;
        $form['email']['#attributes']['disabled'] = TRUE;

        $form['submit_ok']['#value'] = $this->t('Yes, all fine');
        $form['submit_ok']['#attributes']['class'][] = 'pull-left';

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
          '#weight' => 9,
        ];
      }
      if ($step == self::STEP_MANUAL_EMAIL) {
        /** @var \Drupal\openy_campaign\Entity\Member $member */
        $member = $form_state->get('member');
        if ($extended_registration && empty($member->getFullName())) {
          // Force user to provide first and last name.
          $form['first_name'] = [
            '#type' => 'textfield',
            '#size' => 60,
            '#maxlength' => 128,
            '#required' => TRUE,
            '#attributes' => [
              'placeholder' => [
                $this->t('Your First Name'),
              ],
            ],
            '#element_required_error' => $this->t('First Name is required.'),
            '#element_validate' => [
              [$this, 'elementValidateRequired'],
            ],
            '#weight' => 2,
          ];
          $form['last_name'] = [
            '#type' => 'textfield',
            '#size' => 60,
            '#maxlength' => 128,
            '#required' => TRUE,
            '#attributes' => [
              'placeholder' => [
                $this->t('Your Last Name'),
              ],
            ],
            '#element_required_error' => $this->t('Last Name is required.'),
            '#element_validate' => [
              [$this, 'elementValidateRequired'],
            ],
            '#weight' => 3,
          ];
        }
        $form['submit_ok']['#value'] = $this->t('OK');
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
          //'#title_display' => 'hidden',
          '#required' => TRUE,
          '#attributes' => [
            'placeholder' => [
              $this->t('Choose which Group to participate in'),
            ],
          ],
        ];
        $form['submit_ok']['#value'] = $this->t('Continue');
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
        //'#title_display' => 'hidden',
        '#required' => TRUE,
        '#attributes' => [
          'placeholder' => [
            $this->t('Choose which Location to participate in'),
          ],
        ],
      ];
    }

    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $step = $form_state->getValue('step');

    if ($step == self::STEP_MEMBER_ID) {
      $campaignID = $form_state->getValue('campaign_id');
      $membershipID = $form_state->getValue('membership_id');
      $extended_registration = $form_state->getValue('extended_registration');

      /** @var \Drupal\node\Entity\Node $campaign */
      $campaign = $this->nodeStorage->load($campaignID);

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

      $registrationType = 'site';
      // Check if we are need to output the mobile version.
      if (!empty($_GET['mobile'])) {
        $registrationType = 'mobile';
      }

      /** @var \Drupal\openy_campaign\Entity\Member $member Load or create Temporary Member object. Will be saved by submit. */
      $member = Member::loadMemberFromCRMData($membershipID);
      // Membership ID must be valid Personify entry only if extended registration is disabled.
      // Because for enabled extended registration it could be also invite code, not membership ID.
      if (!$extended_registration && (($member instanceof Member === FALSE) || empty($member))) {
        $form_state->setErrorByName('membership_id', $errorMembershipId);
        return;
      }
      // If member was found in Personify - process they as usual.
      if ($member instanceof Member) {
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

        $personifyEmail = $member->getPersonifyEmail();
        if (!empty($personifyEmail)) {
          $form_state->set('email', $personifyEmail);
          $form_state->setTemporaryValue('personify_email', $personifyEmail);
        }
      }
      else {
        // This was an invite code.
        $member = Member::loadMemberFromInvite($membershipID);

        /** @var \Drupal\openy_campaign\Entity\MemberCampaign $memberCampaign Create temporary MemberCampaign entity. Will be saved by submit. */
        $memberCampaign = MemberCampaign::createMemberCampaign($member, $campaign, $registrationType);
        if (($memberCampaign instanceof MemberCampaign === FALSE) || empty($memberCampaign)) {
          $form_state->setErrorByName('membership_id', $errorDefault);
          return;
        }
      }

      // Save Member and MemberCampaign entities in storage to save by submit.
      $form_state->set('status', FALSE);
      $form_state->set('member', $member);
      $form_state->set('campaign', $campaign);
      $form_state->set('member_campaign', $memberCampaign);
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

    // If there are any form errors, re-display the form.
    if ($form_state->hasAnyErrors()) {
      $response->addCommand(new ReplaceCommand('#' . static::$containerId, $form));
      return $response;
    }

    $step = $form_state->getValue('step');
    $triggering_element = $form_state->getTriggeringElement();
    $email = $form_state->get('email');
    $extended_registration = $form_state->getValue('extended_registration');

    $campaignID = $form_state->getValue('campaign_id');
    $membershipID = $form_state->getValue('membership_id');

    // Process step 1.
    if ($step == self::STEP_MEMBER_ID) {
      // If a member already registered for this campaign, log them in here.
      if ($memberCampaignID = MemberCampaign::findMemberCampaign($membershipID, $campaignID)) {
        $response = new AjaxResponse();
        $config = $this->config('openy_campaign.general_settings');
        $storage = $form_state->getStorage();

        /** @var \Drupal\node\Entity\Node $campaign Campaign object. */
        $campaign = $storage['campaign'];
        $campaignStartDate = new \DateTime($campaign->get('field_campaign_start_date')
          ->getString());
        // If Campaign is not started.
        if ($campaignStartDate >= new \DateTime()) {
          $msgNotStarted = $config->get('error_register_checkins_not_started');
          $msgNotStarted = check_markup($msgNotStarted['value'], $msgNotStarted['format']);
          // TODO: use hook_theme instead of inline template.
          $wrappedModalMessage = '<div class="message-wrapper">' . $msgNotStarted . '</div>';
          $modalTitle = $this->t('Thank you!');
        }
        else {
          MemberCampaign::login($membershipID, $campaignID);
          $msgSuccess = $config->get('successful_login');
          $modalMessage = check_markup($msgSuccess['value'], $msgSuccess['format']);
          // TODO: use hook_theme instead of inline template.
          $wrappedModalMessage = '<div class="message-wrapper">' . $modalMessage . '</div>';
          $modalTitle = $this->t('Thank you!');
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
      if (!empty($email)) {
        $form_state->setValue('step', self::STEP_CONFIRM_EMAIL);
      }
      else {
        $form_state->setValue('step', self::STEP_MANUAL_EMAIL);
      }
    }

    // Rebuild form for step 2
    if ($step == self::STEP_CONFIRM_EMAIL) {
      // Yes, all fine.
      if ($triggering_element['#name'] == 'submit_ok') {
        if ($extended_registration) {
          $form_state->set('email', $form_state->getValue('email'));
          $form_state->setValue('step', self::STEP_WHERE_ARE_YOU_FROM);
        }
        else {
          // Jump to submit step.
          $email = $form_state->getValue('email');
          $step = self::STEP_MANUAL_EMAIL;
        }
      }
      // No, change.
      else {
        $form_state->setValue('step', self::STEP_MANUAL_EMAIL);
      }
    }

    if ($extended_registration && ($step == self::STEP_MANUAL_EMAIL)) {
      $form_state->set('first_name', $form_state->getValue('first_name'));
      $form_state->set('last_name', $form_state->getValue('last_name'));
      $form_state->set('email', $form_state->getValue('email'));
      $form_state->setValue('step', self::STEP_WHERE_ARE_YOU_FROM);
    }

    if ($step == self::STEP_WHERE_ARE_YOU_FROM) {
      $form_state->set('where_are_you_from', $form_state->getValue('where_are_you_from'));
      $form_state->setValue('step', self::STEP_WHERE_ARE_YOU_FROM_SPECIFY);
      // Check if there are second level items for selected "Where are you from".
      // In case if not - skip "Specify" step and go finish registration, using 1st level term.
      $options = $this->extendedRegistrationService->getWhereAreYouFromSpecifyOptions($form_state->get('where_are_you_from'));
      if (empty($options)) {
        $step = self::STEP_WHERE_ARE_YOU_FROM_SPECIFY;
        $form_state->setValue('where_are_you_from_specify', "term_{$form_state->get('where_are_you_from')}");
      }
    }

    // Registration handler.
    // Has to be last step, depending on registration type.
    if (
      ($extended_registration && $step == self::STEP_WHERE_ARE_YOU_FROM_SPECIFY) ||
      (!$extended_registration && $step == self::STEP_MANUAL_EMAIL)
    ) {
      $storage = $form_state->getStorage();

      /** @var \Drupal\node\Entity\Node $campaign Campaign object. */
      $campaign = $storage['campaign'];
      $campaignStartDate = new \DateTime($campaign->get('field_campaign_start_date')->getString());
      $campaignEndDate = new \DateTime($campaign->get('field_campaign_end_date')->getString());

      /** @var \Drupal\openy_campaign\Entity\Member $member Member entity. */
      $member = $storage['member'];
      /** @var \Drupal\openy_campaign\Entity\MemberCampaign $memberCampaign MemberCampaign entity. */
      $memberCampaign = $storage['member_campaign'];

      // Update email.
      if (!empty($email)) {
        $member->setEmail($email);
      }
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
        $where_are_you_from = $form_state->get('where_are_you_from');
        $where_are_you_from_specify = explode('_', $form_state->getValue('where_are_you_from_specify'));
        if (reset($where_are_you_from_specify) == 'node') {
          $memberCampaign->setWhereAreYouFrom($where_are_you_from);
          $memberCampaign->setWhereAreYouFromSpecify(end($where_are_you_from_specify));
          $member->setBranchId(end($where_are_you_from_specify));
        }
        else {
          $memberCampaign->setWhereAreYouFrom(end($where_are_you_from_specify));
          $member->setWhereAreYouFrom(end($where_are_you_from_specify));
        }
      }
      $member->save();

      // Define visits goal.
      $memberCampaign->defineGoal();
      $memberCampaign->save();

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
      $this->regularUpdater->createQueue($dateFrom, $dateTo, $membersData);

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
        $modalTitle = $this->t('Thank you!');

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

    // Rebuild form with new $form and $form_state values.
    $new_form = $this->formBuilder
      ->rebuildForm($this->getFormId(), $form_state, $form);
    // Refreshing form.
    $response->addCommand(new ReplaceCommand('#' . static::$containerId, $new_form));
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
