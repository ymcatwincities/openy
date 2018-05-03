<?php

namespace Drupal\fhlb_member_user\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\user\UserInterface;

/**
 * Defines the Member user entity.
 *
 * @ingroup fhlb_member_user
 *
 * @ContentEntityType(
 *   id = "member_user",
 *   label = @Translation("Member user"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\fhlb_member_user\MemberUserListBuilder",
 *     "views_data" = "Drupal\fhlb_member_user\MemberUserViewsData",
 *     "form" = {
 *       "default" = "Drupal\fhlb_member_user\Form\MemberUserForm",
 *       "add" = "Drupal\fhlb_member_user\Form\MemberUserForm",
 *       "edit" = "Drupal\fhlb_member_user\Form\MemberUserForm",
 *       "delete" = "Drupal\fhlb_member_user\Form\MemberUserDeleteForm",
 *     },
 *     "access" = "Drupal\fhlb_member_user\MemberUserAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\fhlb_member_user\MemberUserHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "member_user",
 *   admin_permission = "administer member user entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "sub" = "sub",
 *     "uid" = "uid",
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/member_user/add",
 *     "edit-form" = "/admin/structure/member_user/{member_user}/edit",
 *     "delete-form" = "/admin/structure/member_user/{member_user}/delete",
 *     "collection" = "/admin/structure/member_user",
 *   },
 *   field_ui_base_route = "entity.member_user.collection"
 * )
 */
class MemberUser extends ContentEntityBase implements MemberUserInterface {

  use EntityChangedTrait;
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'uid' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    // Use the Diff module to log changes.
    if ($update) {
      $author = \Drupal::currentUser();

      /** @var \Drupal\diff\DiffEntityComparison $diff */
      $diff = \Drupal::service('diff.entity_comparison');

      $comparison = $diff->compareRevisions($this->original, $this);
      foreach ($comparison as $field) {
        if (isset($field['#name']) && $field['#data']['#left'] != $field['#data']['#right']) {

          $message = $this->t('@field was change from @left to @right by user id: @uid', [
            '@field' => $field['#name'],
            '@left' => $field['#data']['#left'],
            '@right' => $field['#data']['#right'],
            '@uid' => $author->id(),
          ]);

          \Drupal::logger('FHLB Member User')->notice($message);
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   *
   * Since we don't have a label, this helps autocomplete.
   */
  public function label() {
    return $this->first_name->value . ' ' . $this->last_name->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * Helper function to define Admin Member Roles.
   *
   * @return array
   *   An array of member roles.
   */
  public function getAdminMemberRoles() {
    return [
      'member_admin',
      'third_party_user',
    ];
  }

  /**
   * Check if current member user has an admin role.
   *
   * @return bool
   *   TRUE if member user has admin member role, FALSE otherwise.
   */
  public function hasAdminMemberRole() {
    foreach ($this->field_fhlb_mem_roles->getValue() as $role) {
      if (in_array($role['target_id'], $this->getAdminMemberRoles())) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['sub'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Subscriber ID'))
      ->setRevisionable(TRUE)
      ->setRequired(TRUE)
      ->setSettings([
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['cust_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Customer ID'))
      ->setDescription(t('The customer id linked to b2c.'))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->setSettings([
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['email'] = BaseFieldDefinition::create('email')
      ->setLabel(t('Email Address'))
      ->setDescription(t('The Email Address of the member.'))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['first_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('First Name'))
      ->setDescription(t('The first name of the member.'))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->setSettings([
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'weight' => -3,
      ])
      ->setDisplayOptions('form', [
        'weight' => -3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['last_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Last Name'))
      ->setDescription(t('The last name of the member.'))
      ->setRequired(TRUE)
      ->setRevisionable(TRUE)
      ->setSettings([
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'weight' => -2,
      ])
      ->setDisplayOptions('form', [
        'weight' => -2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Member user entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
        'region' => 'hidden',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

}
