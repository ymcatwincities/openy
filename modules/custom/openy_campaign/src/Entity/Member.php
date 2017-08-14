<?php

namespace Drupal\openy_campaign\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\openy_campaign\MemberInterface;
use Drupal\personify\PersonifyClient;

/**
 * Defines the Member entity.
 *
 * @ingroup openy_campaign
 *
 * @ContentEntityType(
 *   id = "openy_campaign_member",
 *   label = @Translation("Member entity"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\openy_campaign\Entity\Controller\MemberListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\openy_campaign\Form\MemberForm",
 *       "add" = "Drupal\openy_campaign\Form\MemberForm",
 *       "edit" = "Drupal\openy_campaign\Form\MemberForm",
 *       "delete" = "Drupal\openy_campaign\Form\MemberDeleteForm",
 *     },
 *     "access" = "Drupal\openy_campaign\EntityAccess\MemberAccessControlHandler",
 *   },
 *   base_table = "openy_campaign_member",
 *   admin_permission = "administer content types",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "membership_id",
 *   },
 *   links = {
 *     "canonical" = "/admin/config/openy-entities/openy-campaign-member/{openy_campaign_member}",
 *     "edit-form" = "/admin/config/openy-entities/openy-campaign-member/{openy_campaign_member}/edit",
 *     "delete-form" = "/admin/config/openy-entities/openy-campaign-member/{openy_campaign_member}/delete",
 *     "collection" = "/admin/config/openy-entities/openy-campaign-member/list"
 *   },
 * )
 */
class Member extends ContentEntityBase implements MemberInterface {

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
      ->setDescription(t('The ID of the Membership entity.'))
      ->setReadOnly(TRUE);

    $fields['mail'] = BaseFieldDefinition::create('email')
      ->setLabel(t('Email'))
      ->setDescription(t('The email of this user.'))
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -6,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string',
        'weight' => -6,
      ])
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Email address field to store email address from Personify.
    $fields['personify_email'] = BaseFieldDefinition::create('email')
      ->setLabel(t('Email from Personify'))
      ->setDescription(t('The email of this user from Personify.'))
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -6,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string',
        'weight' => -6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Membership ID field for the member.
    $fields['membership_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Membership ID'))
      ->setDescription(t('The id on the membership card.'))
      ->setSettings([
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -6,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string',
        'weight' => -6,
      ])
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Personify ID field for the member.
    $fields['personify_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Personify ID'))
      ->setDescription(t('The personify id of the member.'))
      ->setSettings([
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the record was created.'));

    $fields['first_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('First name'))
      ->setDefaultValue('')
      ->setDescription(t('Member first name.'))
      ->setSettings([
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -3,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string',
        'weight' => -3,
      ])
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['last_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Last name'))
      ->setDescription(t('Member last name.'))
      ->setSettings([
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -3,
      ])
      ->setDisplayOptions('form', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -3,
      ])
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['birth_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Birthday'))
      ->setDescription(t('The date of birth.'))
      ->setSetting('datetime_type', 'date')
      ->setDisplayOptions('view', array(
        'label' => 'above',
        'type' => 'datetime',
        'weight' => -2,
      ))
      ->setDisplayOptions('form', array(
        'label' => 'above',
        'type' => 'datetime',
        'weight' => -2,
      ))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['is_employee'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('User is an employee'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'boolean',
        'weight' => -1,
      ])
      ->setDisplayOptions('form', [
        'type' => 'boolean',
        'weight' => -1,
      ])
      ->setSettings([
        'on_label' => t('Member is an employee.'),
        'off_label' => t('Member is not an employee.'),
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['branch'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Branch'))
      ->setDescription(t('Member branch ID.'))
      ->setSetting('target_type', 'node')
      ->setSetting('handler', 'default')
      ->setSetting('handler_settings',['target_bundles'=>['branch' => 'branch']] )
      ->setDisplayOptions('view', array(
        'label'  => 'hidden',
        'type'   => 'branch',
        'weight' => 0,
      ))
      ->setDisplayOptions('form', array(
        'type'     => 'entity_reference_autocomplete',
        'weight'   => -5,
        'settings' => array(
          'match_operator'    => 'CONTAINS',
          'size'              => '60',
          'autocomplete_type' => 'tags',
          'placeholder'       => '',
        ),
      ))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['visit_goal'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Visit Goal'))
      ->setDescription(t('Member visit goal.'))
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

    $fields['total_visits'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Visits'))
      ->setDescription(t('Number of visits.'))
      ->setDefaultValue(0);

    $fields['total_bonuses'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Bonuses'))
      ->setDescription(t('Number of bonuses.'))
      ->setDefaultValue(0);

    $fields['created_by_staff'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Created by Staff'))
      ->setDescription(t('Created by Staff'))
      ->setDefaultValue(FALSE);

    $fields['created_on_mobile'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Created using Mobile app'))
      ->setDescription(t('Created using Mobile app'))
      ->setDefaultValue(FALSE);

    $fields['activity_track_swimming'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Swimming'))
      ->setDescription(t('Number of Swimming activities.'))
      ->setDefaultValue(0);

    $fields['activity_track_fitness'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Fitness'))
      ->setDescription(t('Number of Fitness activities.'))
      ->setDefaultValue(0);

    $fields['activity_track_groupx'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Group X'))
      ->setDescription(t('Number of Group X activities.'))
      ->setDefaultValue(0);

    $fields['activity_track_community'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Community'))
      ->setDescription(t('Number of Community activities.'))
      ->setDefaultValue(0);

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
  public function getEmail() {
    return $this->get('mail')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setEmail($mail) {
    $this->set('mail', $mail);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPersonifyEmail() {
    return $this->get('personify_email')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setPersonifyEmail($email) {
    $this->set('personify_email', $email);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMemberId() {
    return $this->get('membership_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getPersonifyId() {
    return $this->get('personify_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setMemberId($member_id) {
    $this->set('membership_id', $member_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFirstName() {
    return $this->get('first_name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setFirstName($value) {
    $this->set('first_name', $value);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLastName() {
    return $this->get('last_name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setLastName($value) {
    $this->set('last_name', $value);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getFullName() {
    $name = $this->getFirstName();
    $name .= ' ';
    $name .= $this->getLastName();

    return $name;
  }

  /**
   * {@inheritdoc}
   */
  public function getBranchId() {
    return $this->get('branch')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setBranchId($target_id) {
    return $this->set('branch', $target_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getVisits() {
    return $this->get('total_visits')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getBonuses() {
    return $this->get('total_bonuses')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setVisits($value) {
    $this->set('total_visits', $value);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setBonuses($value) {
    $this->set('total_bonuses', $value);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isMemberEmployee() {
    return $this->get('is_employee')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function isCreatedByStaff() {
    return $this->get('created_by_staff')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function isCreatedOnMobile() {
    return $this->get('created_on_mobile')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getVisitGoal() {
    return $this->get('visit_goal')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setVisitGoal($value) {
    $this->set('visit_goal', $value);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getBirthDate() {
    return $this->get('birth_date')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setBirthDate($value) {
    return $this->set('birth_date', $value);
  }

  /**
   * Get member rank.
   *
   * @return int
   *   Member rank.
   */
  public function getMemberRank() {
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public static function calculateVisitGoal($member_ids) {
    $goals = [];
    $settings = \Drupal::config('openy_campaign.general_settings');
    // Get information about number of checkins before campaign.
    $current_date = new \DateTime();
    $from_date = new \DateTime($settings->get('date_checkins_start'));
    $to_date = new \DateTime($settings->get('date_checkins_end'));

    if ($to_date > $current_date) {
      $to_date = $current_date;
    }
    $number_weeks = ceil($from_date->diff($to_date)->days / 7);

    $personifyClient = new PersonifyClient();
    $results = $personifyClient->getPersonifyVisitsBatch($member_ids, $from_date, $to_date);

    if (!empty($results->ErrorMessage)) {
      $logger = \Drupal::logger('openy_campaign_queue');
      $logger->alert('Could not retrieve visits information for members for batch operation');
      return [];
    }

    foreach ($results->FacilityVisitCustomerRecord as $past_result) {
      // Get first visit date.
      try {
        $first_visit = new \DateTime($past_result->FirstVisitDate);
      }
      catch (\Exception $e) {
        $first_visit = $from_date;
      }

      $member_weeks = $number_weeks;
      // If user registered after From date, then recalculate number of weeks.
      if ($first_visit > $from_date) {
        $member_weeks = ceil($first_visit->diff($to_date)->days / 7);
      }

      // Calculate a goal for a member.
      $goal = (int) $settings->get('new_member_goal_number');
      if ($past_result->TotalVisits > 0) {
        $limit_goal = $settings->get('limit_goal_number');
        $calculated_goal = ceil((($past_result->TotalVisits / $member_weeks) * 2) + 1);
        $goal = min(max($goal, $calculated_goal), $limit_goal);
      }

      // Visit goal for late members.
      $close_date = new \DateTime($settings->get('date_campaign_close'));
      $count_days = $current_date->diff($close_date)->days;
      // Set visit goal not greater than number of days till the campaign end.
      // TODO: is it correct? As we should still be able to get his visits for
      // the period of campaign.
      $count_days = max(1, $count_days);
      $goal = min($goal, $count_days);
      $goals[$past_result->MasterCustomerId] = $goal;
    }

    return $goals;
  }

}
