<?php

namespace Drupal\ymca_retention\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\ymca_retention\MemberInterface;

/**
 * Defines the Member entity.
 *
 * @ingroup ymca_retention
 *
 * @ContentEntityType(
 *   id = "ymca_retention_member",
 *   label = @Translation("Member entity"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\ymca_retention\Entity\Controller\MemberListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\ymca_retention\Form\MemberForm",
 *       "add" = "Drupal\ymca_retention\Form\MemberForm",
 *       "edit" = "Drupal\ymca_retention\Form\MemberForm",
 *       "delete" = "Drupal\ymca_retention\Form\MemberDeleteForm",
 *     },
 *     "access" = "Drupal\ymca_retention\EntityAccess\MemberAccessControlHandler",
 *   },
 *   base_table = "ymca_retention_member",
 *   admin_permission = "administer ymca_retention_member entity",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "membership_id",
 *   },
 *   links = {
 *     "canonical" = "/admin/config/ymca-entities/ymca-retention-member/{ymca_retention_member}",
 *     "edit-form" = "/admin/config/ymca-entities/ymca-retention-member/{ymca_retention_member}/edit",
 *     "delete-form" = "/admin/config/ymca-entities/ymca-retention-member/{ymca_retention_member}/delete",
 *     "collection" = "/admin/config/ymca-entities/ymca-retention-member/list"
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
      ->setSetting('datetime_type', 'date');

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

    // @todo Make branch id as reference field to mapping entity, which has branch id from personify.
    $fields['branch'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Branch ID'))
      ->setDescription(t('Member branch ID.'))
      ->setSettings([
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ]);

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
    return $this->get('branch')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setBranchId($value) {
    $this->set('branch', $value);
    return $this;
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
  public function setVisits($value) {
    $this->set('total_visits', $value);
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
   * Get member rank.
   *
   * @return int
   *   Member rank.
   */
  public function getMemberRank() {
    return 0;
  }

}
