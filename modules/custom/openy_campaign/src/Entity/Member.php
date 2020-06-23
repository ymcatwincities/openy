<?php

namespace Drupal\openy_campaign\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\openy_campaign\MemberInterface;

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
 *     "canonical" = "/admin/openy/retention-campaign/openy-campaign-member/{openy_campaign_member}",
 *     "edit-form" = "/admin/openy/retention-campaign/openy-campaign-member/{openy_campaign_member}/edit",
 *     "delete-form" = "/admin/openy/retention-campaign/openy-campaign-member/{openy_campaign_member}/delete",
 *     "collection" = "/admin/openy/retention-campaign/openy-campaign-member/list"
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

    // Personify ID field for the member - Master Customer ID.
    $fields['personify_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Personify ID'))
      ->setDescription(t('The personify id of the member.'))
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
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'datetime',
        'weight' => -2,
      ])
      ->setDisplayOptions('form', [
        'label' => 'above',
        'type' => 'datetime',
        'weight' => -2,
      ])
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
      ->setLabel(t('Branch / Facility'))
      ->setDescription(t('Member branch/facility ID.'))
      ->setSetting('target_type', 'node')
      ->setSetting('handler', 'default')
      ->setSetting('handler_settings', ['target_bundles' => ['branch' => 'branch', 'facility' => 'facility']])
      ->setDisplayOptions('view', [
        'label'  => 'hidden',
        'type'   => 'branch',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type'     => 'entity_reference_autocomplete',
        'weight'   => -5,
        'settings' => [
          'match_operator'    => 'CONTAINS',
          'size'              => '60',
          'autocomplete_type' => 'tags',
          'placeholder'       => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['payment_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Payment type'))
      ->setDescription(t('Payment type is one of: FP, P3.'))
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

    $fields['order_number'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Order Number'))
      ->setDescription(t('Member active order number.'))
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

    $fields['member_unit_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Member unit type'))
      ->setDescription(t('Member unit type is one of: Adult, Dual, Family, Youth, Student, Contract.'))
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

    $fields['is_non_y_member'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('User is non-Y member'))
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
        'on_label' => t('Non-Y member'),
        'off_label' => t('Y member'),
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
  public function setMemberId($membership_id) {
    $this->set('membership_id', $membership_id);
    return $this;
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
  public function setPersonifyId($personify_id) {
    $this->set('personify_id', $personify_id);
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

    return trim($name);
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
  public function isMemberEmployee() {
    return $this->get('is_employee')->value;
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
   * {@inheritdoc}
   */
  public function getPaymentType() {
    return $this->get('payment_type')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setPaymentType($value) {
    $this->set('payment_type', $value);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOrderNumber() {
    return $this->get('order_number')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setOrderNumber($value) {
    $this->set('order_number', $value);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getMemberUnitType() {
    return $this->get('member_unit_type')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setMemberUnitType($value) {
    $this->set('member_unit_type', $value);
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
  public function setNonYMember($value) {
    $this->set('is_non_y_member', $value);
    return $this;
  }

  /**
   * Load Member from CRM values.
   *
   * @param $membershipID
   *   int Membership ID.
   *
   * @return bool | \Drupal\openy_campaign\Entity\Member
   *   FALSE or Member object.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public static function loadMemberFromCRMData($membershipID) {
    /** @var $client \Drupal\openy_campaign\CRMClientInterface */
    $client = \Drupal::getContainer()->get('openy_campaign.client_factory')->getClient();

    $resultsCRM = $client->getMemberInformation($membershipID);
    if (!$resultsCRM->Success) {
      \Drupal::logger('openy_campaign')
        ->error('Failed getting information from CRM for card ID @membershipID', ['@membershipID' => $membershipID]);

      return FALSE;
    }

    // Get info from CRM.
    $email = openy_campaign_clean_personify_email($resultsCRM->PrimaryEmail);
    if (!empty($resultsCRM->BirthDate)) {
      $birthdate = new \DateTime($resultsCRM->BirthDate);
      $birthdate = $birthdate->format('Y-m-d');
    }

    // Find branch from Mapping entity. It connects Branch ID from CRM and Branch node on the site.
    $branch = Mapping::getBranchByPersonifyId($resultsCRM->BranchId);

    // Transform ProductCode into MemberUnitType.
    // Trying to find type as a part of the product code among allowed field's values.
    $productCode = '';
    $productCodeFull = strtolower($resultsCRM->ProductCode);
    $allowedValues = [];
    $membershipUnitTypesSettings = [];
    foreach (\Drupal::getContainer()->get('entity.manager')->getFieldDefinitions('node', 'campaign') as $field_name => $field_definition) {
      if ($field_name == 'field_campaign_membership_u_t') {
        /** var \Drupal\Core\Field\FieldDefinitionInterface $field_definition */
        $membershipUnitTypesSettings = $field_definition->getSettings();
        $allowedValues = array_map(
          function ($v) {
            return strtolower($v);
          },
          array_keys($membershipUnitTypesSettings['allowed_values'])
        );
        break;
      }
    }

    if (!empty($membershipUnitTypesSettings)) {
      foreach ($allowedValues as $allowedValue) {
        if (strpos($productCodeFull, $allowedValue) !== FALSE) {
          $productCode = $allowedValue;
          break;
        }
      }
    }

    // Create Member entity.
    $memberValues = [
      'membership_id' => $membershipID,
      'personify_id' => $resultsCRM->MasterCustomerId,
      'mail' => $email,
      'personify_email' => $email,
      'first_name' => $resultsCRM->FirstName,
      'last_name' => $resultsCRM->LastName,
      'is_employee' => !empty($resultsCRM->ProductCode) && strpos($resultsCRM->ProductCode, 'STAFF'),
      'birth_date' => (isset($birthdate)) ? $birthdate : NULL,
      // Add these fields to CRM API.
      'branch' => !empty($branch) ? $branch : '',
      'order_number' => $resultsCRM->OrderNumber,
      'payment_type' => '',
      'member_unit_type' => $productCode,
      'is_non_y_member' => FALSE,
    ];

    // Load Member by unique Membership ID.
    $query = \Drupal::entityQuery('openy_campaign_member')
      ->condition('membership_id', $membershipID);
    $result = $query->execute();
    if (!empty($result)) {
      $memberID = reset($result);
      $member = self::load($memberID);

      // Update Member entity with values from CRM.
      foreach ($memberValues as $field => $value) {
        $member->set($field, $value);
      }

      return $member;
    }

    /** @var Member $member Otherwise create temporary Member object. Will be saved later. */
    $member = \Drupal::entityTypeManager()
      ->getStorage('openy_campaign_member')
      ->create($memberValues);

    return $member;
  }

  /**
   * Load Member by invite code.
   *
   * @param $invite_code
   *   int Invite code.
   *
   * @return bool|\Drupal\openy_campaign\Entity\Member
   *   FALSE or Member object.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public static function loadMemberFromInvite($invite_code) {
    // Create Member entity.
    $memberValues = [
      'membership_id' => $invite_code,
      'is_non_y_member' => TRUE,
    ];

    // Load Member by unique Membership ID.
    $query = \Drupal::entityQuery('openy_campaign_member')
      ->condition('membership_id', $invite_code);
    $result = $query->execute();
    if (!empty($result)) {
      $memberID = reset($result);
      $member = Member::load($memberID);

      // Update Member entity with values.
      foreach ($memberValues as $field => $value) {
        $member->set($field, $value);
      }
      return $member;
    }

    /** @var Member $member Otherwise create temporary Member object. Will be saved later. */
    $member = \Drupal::entityTypeManager()
      ->getStorage('openy_campaign_member')
      ->create($memberValues);
    return $member;
  }

}
