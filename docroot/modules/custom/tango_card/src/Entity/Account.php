<?php

namespace Drupal\tango_card\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\tango_card\AccountInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Tango Card account entity.
 *
 * @ingroup tango_card_account
 *
 * @ContentEntityType(
 *   id = "tango_card_account",
 *   label = @Translation("Tango Card account"),
 *   label_plural = @Translation("Tango Card accounts"),
 *   bundle_label = @Translation("Campaign"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\tango_card\Entity\Controller\AccountListBuilder",
 *     "form" = {
 *       "add" = "Drupal\tango_card\Form\AccountForm",
 *       "edit" = "Drupal\tango_card\Form\AccountForm",
 *       "delete" = "Drupal\tango_card\Form\AccountDeleteForm",
 *     },
 *     "access" = "Drupal\tango_card\AccountAccessControlHandler",
 *   },
 *   base_table = "tango_card_account",
 *   admin_permission = "administer tango card",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "remote_id",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "fund-form" = "/admin/config/services/tango-card/accounts/{tango_card_account}/fund",
 *     "delete-form" = "/admin/config/services/tango-card/accounts/{tango_card_account}/delete",
 *     "collection" = "/admin/config/services/tango-card/accounts"
 *   },
 * )
 */
class Account extends ContentEntityBase implements AccountInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    // Standard field, used as unique if primary index.
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setReadOnly(TRUE);

    // Standard field, unique outside of the scope of the current project.
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setReadOnly(TRUE);

    // Account ID.
    $fields['remote_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Account ID'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
      ])
      ->setDisplayConfigurable('form', TRUE);

    // Customer.
    $fields['customer'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Customer'))
      ->setRequired(TRUE)
      ->setDescription(t('The platform’s customer. A customer is a mechanism for denoting a company, department, etc…, an account belongs to.'))
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
      ])
      ->setDisplayConfigurable('form', TRUE);

    // Email.
    $fields['mail'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Email'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'email',
      ])
      ->setDisplayConfigurable('form', TRUE);

    // Credit card token.
    $fields['cc_token'] = BaseFieldDefinition::create('string')
      ->setLabel(t('CC registration token'));

    // Credit card last 4 digits.
    $fields['cc_number'] = BaseFieldDefinition::create('string')
      ->setLabel(t('CC last 4 digits'));

    return $fields;
  }

}
