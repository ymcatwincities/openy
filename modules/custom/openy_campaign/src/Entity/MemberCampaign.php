<?php

namespace Drupal\openy_campaign\Entity;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\node\NodeInterface;
use Drupal\openy_campaign\MemberCampaignInterface;
use Drupal\openy_campaign\MemberInterface;

/**
 * Defines the MemberCampaign entity to store Campaigns assigned to the Member.
 *
 * @ingroup openy_campaign
 *
 * @ContentEntityType(
 *   id = "openy_campaign_member_campaign",
 *   label = @Translation("MemberCampaign entity"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\openy_campaign\Entity\Controller\MemberCampaignListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\openy_campaign\Form\MemberCampaignForm",
 *       "add" = "Drupal\openy_campaign\Form\MemberCampaignForm",
 *       "edit" = "Drupal\openy_campaign\Form\MemberCampaignForm",
 *       "delete" = "Drupal\openy_campaign\Form\MemberCampaignDeleteForm",
 *     },
 *     "access" = "Drupal\openy_campaign\EntityAccess\MemberAccessControlHandler",
 *   },
 *   base_table = "openy_campaign_member_campaign",
 *   admin_permission = "administer content types",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "campaign"
 *   },
 *   links = {
 *     "canonical" = "/admin/openy/retention-campaign/openy-campaign-member-campaign/{openy_campaign_member_campaign}",
 *     "edit-form" = "/admin/openy/retention-campaign/openy-campaign-member-campaign/{openy_campaign_member_campaign}/edit",
 *     "delete-form" = "/admin/openy/retention-campaign/openy-campaign-member-campaign/{openy_campaign_member_campaign}/delete",
 *     "collection" = "/admin/openy/retention-campaign/openy-campaign-member-campaign/list"
 *   },
 * )
 */
class MemberCampaign extends ContentEntityBase implements MemberCampaignInterface {

  /**
   * {@inheritdoc}
   *
   * Define the field properties here.
   *
   * Field name, type and size determine the table structure.
   *
   * In addition, we can define how the field and its content can be manipulated
   * in the GUI. The behaviour of the widgets used can be determined here.
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    // Standard field, used as unique if primary index.
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the MemberCampaign entity.'))
      ->setReadOnly(TRUE);

    // Campaign entity ID field.
    $fields['campaign'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Campaign ID'))
      ->setDescription(t('The id of the Campaign entity. Start typing Campaign name.'))
      ->setSetting('target_type', 'node')
      ->setSetting('handler', 'default')
      ->setSetting('handler_settings', ['target_bundles' => ['campaign' => 'campaign']])
      ->setDisplayOptions('view', [
        'label'  => 'hidden',
        'type'   => 'campaign',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type'     => 'entity_reference_autocomplete',
        'weight'   => 5,
        'settings' => [
          'match_operator'    => 'CONTAINS',
          'size'              => '60',
          'autocomplete_type' => 'tags',
          'placeholder'       => '',
        ],
      ])
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Member entity ID field.
    $fields['member'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Member'))
      ->setDescription(t('The id of the Member entity. Start typing Membership ID.'))
      ->setSettings(['target_type' => 'openy_campaign_member'])
      ->setDisplayOptions('view', [
        'label'  => 'hidden',
        'type'   => 'openy_campaign_member',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type'     => 'entity_reference_autocomplete',
        'weight'   => 5,
        'settings' => [
          'match_operator'    => 'CONTAINS',
          'size'              => '60',
          'autocomplete_type' => 'tags',
          'placeholder'       => '',
        ],
      ])
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the record was created.'));

    // Standard field, used as unique if primary index.
    $fields['goal'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Visit Goal'))
      ->setDescription(t('How many visits member should do to reach the campaign goal.'))
      ->setReadOnly(TRUE)
      ->setSettings([
        'default_value' => 0,
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -1,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string',
        'weight' => -1,
      ])
      ->setDefaultValue(0)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['registration_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Registration Type'))
      ->setDescription(t('The place where the individual was registered.'));

    $fields['where_are_you_from'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Where Are You From?'))
      ->setSettings(['target_type' => 'taxonomy_term'])
      ->setSetting('handler', 'default')
      ->setSetting('handler_settings', ['target_bundles' => ['where_are_you_from' => 'where_are_you_from']])
      ->setDisplayOptions('view', [
        'label'  => 'hidden',
        'type'   => 'where_are_you_from',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type'     => 'entity_reference_autocomplete',
        'weight'   => 5,
        'settings' => [
          'match_operator'    => 'CONTAINS',
          'size'              => '60',
          'autocomplete_type' => 'tags',
          'placeholder'       => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['where_are_you_from_specify'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Where Are You From - Specify'))
      ->setSettings(['target_type' => 'node'])
      ->setSetting('handler', 'default')
      ->setSetting('handler_settings', ['target_bundles' => ['branch' => 'branch', 'facility' => 'facility']])
      ->setDisplayOptions('view', [
        'label'  => 'hidden',
        'type'   => 'where_are_you_from_specify',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type'     => 'entity_reference_autocomplete',
        'weight'   => 5,
        'settings' => [
          'match_operator'    => 'CONTAINS',
          'size'              => '60',
          'autocomplete_type' => 'tags',
          'placeholder'       => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return $this->get('id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getMember() {
    return $this->get('member')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setMember(MemberInterface $member) {
    $this->set('member', $member);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCampaign() {
    return $this->get('campaign')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setCampaign(NodeInterface $campaign) {
    $this->set('campaign', $campaign);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getGoal() {
    return $this->get('goal')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setGoal($goal) {
    $this->set('goal', $goal);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRegistrationType() {
    return $this->get('registration_type')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setRegistrationType($registrationType) {
    $this->set('registration_type', $registrationType);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setWhereAreYouFrom($where_are_you_from) {
    $this->set('where_are_you_from', $where_are_you_from);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setWhereAreYouFromSpecify($where_are_you_from_specify) {
    $this->set('where_are_you_from_specify', $where_are_you_from_specify);
    return $this;
  }

  /**
   * Set the Goal for the MemberCampaign.
   *
   * @return bool
   */
  public function defineGoal() {
    /**
     * To test generate Members, create MemberCampaign record and run code in /devel/php
     *
     * $entity = \Drupal\openy_campaign\Entity\MemberCampaign::load(1);
     * $entity->defineGoal();
     *
     * Needs actual data in order to proceed.
     */

    /** @var \Drupal\node\Entity\Node $campaign Current campaign */
    $campaign = $this->getCampaign();

    // Get all enabled activities list.
    $activitiesOptions = openy_campaign_get_enabled_activities($campaign);

    // For disabled Visits Goal activity.
    if (!in_array('field_prgf_activity_visits', $activitiesOptions)) {
      $this->setGoal(0);
      return TRUE;
    }

    $current = new \DateTime();
    $from = new \DateTime($campaign->field_goal_check_ins_start_date->value);
    $to = new \DateTime($campaign->field_goal_check_ins_end_date->value);

    // We should not call CRM for the future date.
    if ($current < $to) {
      $to = $current;
    }

    $member = $this->getMember();

    /** @var $client \Drupal\openy_campaign\CRMClientInterface */
    $client = \Drupal::getContainer()->get('openy_campaign.client_factory')->getClient();
    // Get total visits from CRM.
    $results = $client->getVisitCountByDate($member->getPersonifyId(), $from, $to);

    $visitsNumber = $results->TotalVisits;

    // Visits goal will be between these 2 values.
    $maxVisitsGoal = $campaign->field_limit_visits_goal->value;
    $minVisitsGoal = $campaign->field_min_visits_goal->value;

    $weeksSpan = ceil($from->diff($to)->days / 7);

    $calculatedGoal = ceil(2 * $visitsNumber / $weeksSpan) + 1;
    $visitsGoal = max($minVisitsGoal, $calculatedGoal);

    $goal = min($visitsGoal, $maxVisitsGoal);

    $this->setGoal($goal);

    return TRUE;
  }

  /**
   * Validate by Target Audience Settings from Campaign.
   *
   * @return array Array of error messages. Will be empty if validation passed.
   */
  public function validateTargetAudienceSettings() {
    $errorMessages = [];

    // @TODO: Enable validation after CRM is updated.
    return [];

    // Age is in the range from Target Audience Setting from Campaign.
    $validateAge = $this->validateMemberAge();
    if (!$validateAge['status']) {
      $errorMessages[] = $validateAge['error'];
    }

    // Member type match Target Audience Setting from Campaign.
    $validateMemberUnitType = $this->validateMemberUnitType();
    if (!$validateMemberUnitType['status']) {
      $errorMessages[] = $validateMemberUnitType['error'];
    }
    // Branch is one of the selected in the Target Audience Setting from Campaign.
    $validateMemberBranch = $this->validateMemberBranch();
    if (!$validateMemberBranch['status']) {
      $errorMessages[] = $validateMemberBranch['error'];
    }

    return $errorMessages;
  }

  /**
   * Check if the member age fit to the Campaign age range.
   *
   * @return array Array with status and error message.
   */
  private function validateMemberAge() {
    /** @var \Drupal\node\Entity\Node $campaign Campaign node object. */
    $campaign = $this->getCampaign();
    /** @var \Drupal\openy_campaign\Entity\Member $member Temporary Member object. Will be saved by submit. */
    $member = $this->getMember();

    $minAge = $campaign->get('field_campaign_age_minimum')->value;
    $maxAge = $campaign->get('field_campaign_age_maximum')->value;

    $birthday = new \DateTime($member->getBirthDate());
    $now = new \DateTime();
    $interval = $now->diff($birthday)->format('%y');

    if (
      (!empty($minAge) && !empty($maxAge) && $interval >= $minAge &&  $interval <= $maxAge) ||
      (!empty($minAge) && empty($maxAge) && $interval >= $minAge) ||
      (empty($minAge) && !empty($maxAge) && $interval <= $maxAge)
    ) {
      return ['status' => TRUE, 'error' => ''];
    }

    return ['status' => FALSE, 'error' => t('Age is not between @min and @max.', ['@min' => $minAge, '@max' => $maxAge])->render()];

  }

  /**
   * Check if the member type fit to the Campaign selected types.
   *
   * @return array Array with status and error message.
   */
  private function validateMemberUnitType() {
    /** @var \Drupal\node\Entity\Node $campaign Campaign node object. */
    $campaign = $this->getCampaign();
    /** @var \Drupal\openy_campaign\Entity\Member $member Temporary Member object. Will be saved by submit. */
    $member = $this->getMember();

    $campaignMemberUnitTypes = $campaign->get('field_campaign_membership_u_t')->getString();
    $memberMemberUnitType = $member->getMemberUnitType();

    if (in_array($memberMemberUnitType, explode(', ', $campaignMemberUnitTypes))) {
      return ['status' => TRUE, 'error' => ''];
    }

    return ['status' => FALSE, 'error' => t('Member unit type does not match types: @types.', ['@types' => $campaignMemberUnitTypes])->render()];
  }

  /**
   * Check if the member branch fit to the Campaign selected branches.
   *
   * @return array Array with status and error message.
   */
  private function validateMemberBranch() {
    /** @var \Drupal\node\Entity\Node $campaign Campaign node object. */
    $campaign = $this->getCampaign();
    /** @var \Drupal\openy_campaign\Entity\Member $member Temporary Member object. Will be saved by submit. */
    $member = $this->getMember();

    $branchesField = $campaign->get('field_campaign_branch_target')->getValue();
    $campaignBranches = [];
    foreach ($branchesField as $branch) {
      $campaignBranches[] = $branch['target_id'];
    }

    $memberBranch = $member->getBranchId();

    if (in_array($memberBranch, $campaignBranches)) {
      return ['status' => TRUE, 'error' => ''];
    }

    return ['status' => FALSE, 'error' => t('Branch is not included.')->render()];
  }

  /**
   * Check if the member age fit to the Campaign payment types.
   *
   * @return array Array with status and error message.
   */
  private function validateMemberPaymentType() {
    /** @var \Drupal\node\Entity\Node $campaign Campaign node object. */
    $campaign = $this->getCampaign();
    /** @var \Drupal\openy_campaign\Entity\Member $member Temporary Member object. Will be saved by submit. */
    $member = $this->getMember();

    $campaignPaymentTypes = $campaign->get('field_campaign_payment_types')->getString();
    $memberPaymentType = $member->getPaymentType();

    if (in_array($memberPaymentType, explode(', ', $campaignPaymentTypes))) {
      return ['status' => TRUE, 'error' => ''];
    }

    return ['status' => FALSE, 'error' => t('Payment type does not match types: @types.', ['@types' => $campaignPaymentTypes])->render()];
  }

  /**
   * Find MemberCampaign ID if it already exists.
   *
   * @param $membershipID
   *   int Membership ID.
   * @param $campaignID
   *   int Campaign node ID.
   *
   * @return int | bool MemberCampaign ID or FALSE
   */
  public static function findMemberCampaign($membershipID, $campaignID) {
    $connection = \Drupal::service('database');
    /** @var \Drupal\Core\Database\Query\Select $query */
    $query = $connection->select('openy_campaign_member', 'm');
    $query->condition('m.membership_id', $membershipID);
    $query->join('openy_campaign_member_campaign', 'mc', 'm.id = mc.member');
    $query->condition('mc.campaign', $campaignID);
    $query->fields('mc', ['id']);
    $memberCampaignRes = $query->execute()->fetchField();

    return (!empty($memberCampaignRes)) ? $memberCampaignRes : FALSE;
  }

  /**
   * Create MemberCampaign entity.
   *
   * @param \Drupal\openy_campaign\Entity\Member $member
   *   Member entity.
   * @param \Drupal\node\Entity\Node $campaign
   *   Campaign node.
   * @param string $registrationType
   *
   * @return bool | \Drupal\openy_campaign\Entity\MemberCampaign
   *   FALSE or MemberCampaign entity
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public static function createMemberCampaign($member, $campaign, $registrationType = 'site') {
    /** @var \Drupal\openy_campaign\Entity\MemberCampaign $memberCampaign Create temporary MemberCampaign object. Will be saved later. */
    $memberCampaign = \Drupal::entityTypeManager()
      ->getStorage('openy_campaign_member_campaign')
      ->create([
        'campaign' => $campaign,
        'member' => $member,
        'registration_type' => $registrationType,
      ]);

    if (($memberCampaign instanceof MemberCampaign === FALSE) || empty($memberCampaign)) {
      \Drupal::logger('openy_campaign')
        ->error('Error while creating MemberCampaign temporary object.');

      return FALSE;
    }

    return $memberCampaign;
  }

  /**
   * Login member by Campaign ID. Save it to SESSION.
   *
   * @param $membershipID
   *   int Membership card number.
   * @param $campaignID
   *   int Campaign node ID.
   */
  public static function login($membershipID, $campaignID) {
    $campaignIDs = [];
    $membershipIDs = [];
    $fullNames = [];
    $memberIDs = [];

    $request = \Drupal::request();
    $session = $request->getSession();

    if (!empty($session->get('openy_campaign'))) {
      $openyCampaignSession = $session->get('openy_campaign');
      $campaignIDs = $openyCampaignSession['campaign_ids'];
      $membershipIDs = $openyCampaignSession['membership_ids'];
      $fullNames = $openyCampaignSession['full_names'];
      $memberIDs = $openyCampaignSession['member_ids'];
    }

    // Add new Campaign ID, MembershipID and Full name to SESSION.
    if (!in_array($campaignID, $campaignIDs) || !in_array($membershipID, $membershipIDs)) {
      // Load Member by unique Membership ID.
      $query = \Drupal::entityQuery('openy_campaign_member')
        ->condition('membership_id', $membershipID);
      $result = $query->execute();
      if (!empty($result)) {
        $memberID = reset($result);
        $member = Member::load($memberID);
      }

      $campaignIDs[] = $campaignID;
      $membershipIDs[$campaignID] = $membershipID;
      $fullName = (!empty($member) && !empty($member->getFullName())) ? $member->getFullName() : t('Team member');
      $fullNames[$campaignID] = $fullName;
      $memberId = !empty($member) ? $member->id() : '';
      $memberIDs[$campaignID] = $memberId;

      Cache::invalidateTags(['member:' . $memberId]);

      $session->set('openy_campaign', [
        'member_ids' => $memberIDs,
        'campaign_ids' => $campaignIDs,
        'membership_ids' => $membershipIDs,
        'full_names' => $fullNames,
      ]);
    }
  }

  /**
   * Logout member by Campaign ID. Delete it from SESSION.
   *
   * @param $campaignID
   *   int Campaign node ID.
   */
  public static function logout($campaignID) {
    $campaignIDs = [];
    $membershipIDs = [];
    $fullNames = [];
    $memberIDs = [];

    $request = \Drupal::request();
    $session = $request->getSession();

    if (!empty($session->get('openy_campaign'))) {
      $openyCampaignSession = $session->get('openy_campaign');
      $campaignIDs = $openyCampaignSession['campaign_ids'];
      $membershipIDs = $openyCampaignSession['membership_ids'];
      $fullNames = $openyCampaignSession['full_names'];
      $memberIDs = $openyCampaignSession['member_ids'];
    }

    // Delete Campaign ID, Membership ID, internal Member ID and Full name from SESSION.
    if (in_array($campaignID, $campaignIDs)) {
      $newCampaignIDs = array_diff($campaignIDs, [$campaignID]);

      $membershipID = $membershipIDs[$campaignID];
      $newMembershipIDs = array_diff($membershipIDs, [$membershipID]);

      $fullName = $fullNames[$campaignID];
      $newFullNames = array_diff($fullNames, [$fullName]);

      $memberID = $fullNames[$campaignID];
      $newMemberIDs = array_diff($memberIDs, [$memberID]);

      Cache::invalidateTags(['member:' . $memberID]);

      $session->set('openy_campaign', [
        'campaign_ids' => $newCampaignIDs,
        'membership_ids' => $newMembershipIDs,
        'full_names' => $newFullNames,
        'member_ids' => $newMemberIDs,
      ]);
    }
  }

  /**
   * Check if member already logged in for this Campaign.
   *
   * @param $campaignID
   *   int Campaign node ID.
   *
   * @return bool
   */
  public static function isLoggedIn($campaignID) {
    $campaignIDs = [];
    $request = \Drupal::request();
    $session = $request->getSession();
    if (!empty($session->get('openy_campaign'))) {
      $openyCampaignSession = $session->get('openy_campaign');
      $campaignIDs = $openyCampaignSession['campaign_ids'];
    }

    return in_array($campaignID, $campaignIDs);
  }

  /**
   * Get campaign ids, membership id and full name from SESSION.
   *
   * @param int $campaignId
   *   Campaign node ID.
   *
   * @return array MemberCampaign data from SESSION.
   */
  public static function getMemberCampaignData($campaignId) {
    $request = \Drupal::request();
    $session = $request->getSession();

    $openyCampaignSession = $session->get('openy_campaign', [
      'campaign_ids' => [],
      'member_ids' => [],
      'membership_ids' => [],
      'full_names' => [],
    ]);

    $membershipIDs = $openyCampaignSession['membership_ids'];
    $fullNames = $openyCampaignSession['full_names'];
    $memberIDs = $openyCampaignSession['member_ids'];

    return [
      'campaign_id' => $campaignId,
      'membership_id' => !empty($membershipIDs[$campaignId]) ? $membershipIDs[$campaignId] : '',
      'full_name' => !empty($fullNames[$campaignId]) ? $fullNames[$campaignId] : '',
      'member_id' => !empty($memberIDs[$campaignId]) ? $memberIDs[$campaignId] : '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function save() {
    $return = parent::save();

    $isAllowedToCreateAnEntry = FALSE;

    /** @var \Drupal\node\NodeInterface $campaign */
    $campaign = $this->getCampaign();
    foreach ($campaign->get('field_ways_to_earn_entries')->getValue() as $item) {
      if ($item['value'] == MemberGame::TYPE_REGISTER) {
        $isAllowedToCreateAnEntry = TRUE;
        break;
      }
    }

    if ($isAllowedToCreateAnEntry) {
      // Create Instant-Win game chance.
      $game = MemberGame::create([
        'member' => $this->id(),
        'chance_type' => MemberGame::TYPE_REGISTER,
      ]);
      $game->save();
      Cache::invalidateTags(['member_campaign:' . $this->id()]);
    }

    return $return;

  }

}
