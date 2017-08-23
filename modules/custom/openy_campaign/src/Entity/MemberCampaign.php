<?php

namespace Drupal\openy_campaign\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\node\NodeInterface;
use Drupal\node\Entity\Node;
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
 *     "canonical" = "/admin/config/openy-entities/openy-campaign-member-campaign/{openy_campaign_member_campaign}",
 *     "edit-form" = "/admin/config/openy-entities/openy-campaign-member-campaign/{openy_campaign_member_campaign}/edit",
 *     "delete-form" = "/admin/config/openy-entities/openy-campaign-member-campaign/{openy_campaign_member_campaign}/delete",
 *     "collection" = "/admin/config/openy-entities/openy-campaign-member-campaign/list"
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
      ->setSetting('handler_settings',['target_bundles'=>['campaign' => 'campaign']] )
      ->setDisplayOptions('view', array(
        'label'  => 'hidden',
        'type'   => 'campaign',
        'weight' => 0,
      ))
      ->setDisplayOptions('form', array(
        'type'     => 'entity_reference_autocomplete',
        'weight'   => 5,
        'settings' => array(
          'match_operator'    => 'CONTAINS',
          'size'              => '60',
          'autocomplete_type' => 'tags',
          'placeholder'       => '',
        ),
      ))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Member entity ID field.
    $fields['member'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Member'))
      ->setDescription(t('The id of the Member entity. Start typing Membership ID.'))
      ->setSettings(['target_type' => 'openy_campaign_member'])
      ->setDisplayOptions('view', array(
        'label'  => 'hidden',
        'type'   => 'openy_campaign_member',
        'weight' => 0,
      ))
      ->setDisplayOptions('form', array(
        'type'     => 'entity_reference_autocomplete',
        'weight'   => 5,
        'settings' => array(
          'match_operator'    => 'CONTAINS',
          'size'              => '60',
          'autocomplete_type' => 'tags',
          'placeholder'       => '',
        ),
      ))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Standard field, used as unique if primary index.
    $fields['goal'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Goal'))
      ->setDescription(t('How many visits member should do to reach the campaign goal.'))
      ->setReadOnly(TRUE);

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
    return $this->get('goal');
  }

  /**
   * {@inheritdoc}
   */
  public function setGoal($goal) {
    $this->set('goal', $goal);
    return $this;
  }

  /**
   * Set the Goal for the Member for this campaign.
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

    $campaign = $this->getCampaign();

    $current = new \DateTime();
    $from = new \DateTime($campaign->field_check_ins_start_date->value);
    $to = new \DateTime($campaign->field_check_ins_end_date->value);

    // We should not call CRM for the future date.
    if ($current < $to) {
      $to = $current;
    }

    $memberId = $this->member->entity->getMemberId();

    /** @var $client \Drupal\openy_campaign\CRMClientInterface */
    $client = \Drupal::getContainer()->get('openy_campaign.client_factory')->getClient();

    $results = $client->getVisitCountByDate($memberId, $from, $to);
    var_dump($results);
    return;

    $visitsNumber = 1; // This should come from API's call.

    $maxGoal = 5; // Add field to Campaign node and use that value.

    $weeksSpan = ceil($from_date->diff($to_date)->days / 7);
    $calculatedGoal = ceil(2 * $visitsNumber / $weeksSpan) + 1;

    $goal = min($calculatedGoal, $maxGoal);

    $this->setGoal($goal);
  }

  /**
   * Validate by Target Audience Settings from Campaign.
   *
   * @return array Array of error messages. Will be empty if validation passed.
   */
  public function validateTargetAudienceSettings() {
    $errorMessages = [];

    // Age is in the range from Target Audience Setting from Campaign.
    $validateAge = $this->validateMemberAge();
    if (!$validateAge['status']) {
      $errorMessages[] = $validateAge['error'];
    }

    // TODO Uncomment this after all data will be available from CRM API
//    // Member type match Target Audience Setting from Campaign.
//    $validateMemberUnitType = $this->validateMemberUnitType();
//    if (!$validateMemberUnitType['status']) {
//      $errorMessages[] = $validateMemberUnitType['error'];
//    }
//    // Branch is one of the selected in the Target Audience Setting from Campaign.
//    $validateMemberBranch = $this->validateMemberBranch();
//    if ($validateMemberBranch['status']) {
//      $errorMessages[] = $validateMemberBranch['error'];
//    }
//    // Payment type is of the selected in the Target Audience Setting from Campaign.
//    $validateMemberPaymentType = $this->validateMemberPaymentType();
//    if ($validateMemberPaymentType['status']) {
//      $errorMessages[] = $validateMemberPaymentType['error'];
//    }

    return $errorMessages;
  }

  /**
   * Check if the member age fit to the Campaign age range.
   *
   * @return array Array with status and error message.
   */
  private function validateMemberAge() {
    /** @var Node $campaign Campaign node object. */
    $campaign = $this->getCampaign();
    /** @var Member $member Temporary Member object. Will be saved by submit. */
    $member = $this->getMember();

    $minAge = $campaign->get('field_campaign_age_minimum')->value;
    $maxAge = $campaign->get('field_campaign_age_maximum')->value;

    $birthday = new \DateTime($member->getBirthDate());
    $now = new \DateTime();
    $interval = $now->diff($birthday)->format('%y');

    if ($interval >= $minAge) {
      if (!empty($maxAge) &&  $interval <= $maxAge) {
        return ['status' => TRUE, 'error' => ''];
      }
      return ['status' => TRUE, 'error' => ''];
    }

    return ['status' => FALSE, 'error' => t('Age is not between @min and @max', ['@min' => $minAge, '@max' => $maxAge])];
  }

  /**
   * Check if the member type fit to the Campaign selected types.
   *
   * @return array Array with status and error message.
   */
  private function validateMemberUnitType() {
    /** @var Node $campaign Campaign node object. */
    $campaign = $this->getCampaign();
    /** @var Member $member Temporary Member object. Will be saved by submit. */
    $member = $this->getMember();

    $campaignMemberUnitTypes = $campaign->get('field_campaign_membership_u_t')->getString();
    $memberMemberUnitType = $member->getMemberUnitType();

    if (in_array($memberMemberUnitType, explode(', ', $campaignMemberUnitTypes))) {
      return ['status' => TRUE, 'error' => ''];
    }

    return ['status' => FALSE, 'error' => t('Member unit type does not match types: @types', ['@types' => $campaignMemberUnitTypes])];
  }

  /**
   * Check if the member branch fit to the Campaign selected branches.
   *
   * @return array Array with status and error message.
   */
  private function validateMemberBranch() {
    /** @var Node $campaign Campaign node object. */
    $campaign = $this->getCampaign();
    /** @var Member $member Temporary Member object. Will be saved by submit. */
    $member = $this->getMember();

    $campaignBranches = $campaign->get('field_campaign_branches')->getString();
    $memberBranch = $member->getBranchId();

    if (in_array($memberBranch, explode(', ', $campaignBranches))) {
      return ['status' => TRUE, 'error' => ''];
    }

    return ['status' => FALSE, 'error' => t('Branch is not included.')];
  }

  /**
   * Check if the member age fit to the Campaign payment types.
   *
   * @return array Array with status and error message.
   */
  private function validateMemberPaymentType() {
    /** @var Node $campaign Campaign node object. */
    $campaign = $this->getCampaign();
    /** @var Member $member Temporary Member object. Will be saved by submit. */
    $member = $this->getMember();

    $campaignPaymentTypes = $campaign->get('field_campaign_payment_types')->getString();
    $memberPaymentType = $member->getPaymentType();

    if (in_array($memberPaymentType, explode(', ', $campaignPaymentTypes))) {
      return ['status' => TRUE, 'error' => ''];
    }

    return ['status' => FALSE, 'error' => t('Payment type does not match types: @types', ['@types' => $campaignPaymentTypes])];
  }

  /**
   * Find MemberCampaign ID if it already exists.
   *
   * @param $membershipID int Membership ID.
   * @param $campaignID int Campaign node ID.
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
   * @param $member Member Member entity.
   * @param $campaign Node Campaign node.
   *
   * @return bool | \Drupal\openy_campaign\Entity\MemberCampaign
   *   FALSE or MemberCampaign entity
   */
  public static function createMemberCampaign($member, $campaign) {
    /** @var MemberCampaign $memberCampaign Create temporary MemberCampaign object. Will be saved later. */
    $memberCampaign = \Drupal::entityTypeManager()
      ->getStorage('openy_campaign_member_campaign')
      ->create([
        'campaign' => $campaign,
        'member' => $member,
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
   * @param $membershipID int Membership card number.
   * @param $campaignID int Campaign node ID.
   */
  public static function login($membershipID, $campaignID) {
    // Get Campaign IDs
    $campaignIDs = self::getCampaignIds();

    // Add new Campaign ID, MembershipID and Full name to SESSION
    if (!in_array($campaignID, $campaignIDs)) {
      $request = \Drupal::request();
      $session = $request->getSession();

      // Load Member by unique Membership ID.
      $query = \Drupal::entityQuery('openy_campaign_member')
        ->condition('membership_id', $membershipID);
      $result = $query->execute();
      if (!empty($result)) {
        $memberID = reset($result);
        $member = Member::load($memberID);
      }

      $campaignIDs[] = $campaignID;
      $session->set('openy_campaign', [
        'campaign_ids' => $campaignIDs,
        'membership_id' => $membershipID,
        'full_name' => (!empty($member) && !empty($member->getFullName())) ? $member->getFullName() : t('Team member'),
      ]);
    }
  }

  /**
   * Logout member by Campaign ID. Delete it from SESSION.
   *
   * @param $campaignID int Campaign node ID.
   */
  public static function logout($campaignID) {
    $campaignIDs = self::getCampaignIds();

    $request = \Drupal::request();
    $session = $request->getSession();

    // Delete Campaign ID from SESSION
    if (in_array($campaignID, $campaignIDs)) {
      $newCampaignIDs = array_diff($campaignIDs, [$campaignID]);
      $session->set('openy_campaign', ['campaign_ids' => $newCampaignIDs]);
    }
    // Delete Membership ID from SESSION
    $openyCampaignSession = $session->get('openy_campaign');
    if (!empty($openyCampaignSession['membership_id'])) {
      $session->set('openy_campaign', ['membership_id' => '', 'full_name' => '']);
    }
  }

  /**
   * Check if member already logged in for this Campaign.
   *
   * @param $campaignID int Campaign node ID.
   *
   * @return bool
   */
  public static function isLoggedIn($campaignID) {
    $campaignIDs = self::getCampaignIds();

    return (in_array($campaignID, $campaignIDs)) ? TRUE : FALSE;
  }

  /**
   * Get campaign ids, membership id and full name from SESSION.
   *
   * @return array MemberCampaign data from SESSION.
   */
  public static function getMemberCampaignData() {
    $request = \Drupal::request();
    $session = $request->getSession();
    $openyCampaignSession = $session->get('openy_campaign', [
      'campaign_ids' => [],
      'membership_id' => '',
      'full_name' => '',
    ]);

    return $openyCampaignSession;
  }
  /**
   * Get all Campaign IDs user logged in.
   *
   * @return array Array of Campaign IDs
   */
  private static function getCampaignIds() {
    $campaignIDs = [];
    $request = \Drupal::request();
    $session = $request->getSession();
    if (!empty($session->get('openy_campaign'))) {
      $openyCampaignSession = $session->get('openy_campaign');
      $campaignIDs = $openyCampaignSession['campaign_ids'];
    }

    return $campaignIDs;
  }
}
