<?php

namespace Drupal\tango_card\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\tango_card\CampaignInterface;
use Drupal\user\UserInterface;

/**
 * Defines the TangoCardCampaign entity.
 *
 * @ingroup tango_card_campaign
 *
 * @ContentEntityType(
 *   id = "tango_card_campaign",
 *   label = @Translation("Tango Card campaign"),
 *   label_plural = @Translation("Tango Card campaigns"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\tango_card\Entity\Controller\CampaignListBuilder",
 *     "form" = {
 *       "add" = "Drupal\tango_card\Form\CampaignForm",
 *       "edit" = "Drupal\tango_card\Form\CampaignForm",
 *       "delete" = "Drupal\tango_card\Form\CampaignDeleteForm",
 *     },
 *     "access" = "Drupal\tango_card\CampaignAccessControlHandler",
 *   },
 *   base_table = "tango_card_campaign",
 *   admin_permission = "administer tango card",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/system/tango-card/campaigns/{tango_card_campaign}/edit",
 *     "delete-form" = "/admin/config/system/tango-card/campaigns/{tango_card_campaign}/delete",
 *     "collection" = "/admin/config/system/tango-card/campaigns"
 *   },
 * )
 */
class Campaign extends ContentEntityBase implements CampaignInterface {

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

    // Campaign name.
    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setRequired(TRUE)
      ->setSettings(array(
        'max_length' => 255,
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
      ))
      ->setDisplayConfigurable('form', TRUE);

    // Notification flag.
    $fields['notification_enabled'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Enable email notification'))
      ->setDisplayOptions('form', array(
        'type' => 'boolean_checkbox',
        'settings' => array(
          'display_label' => TRUE,
        ),
      ))
      ->setDefaultValue(FALSE);

    // Notification: From.
    $fields['notification_from'] = BaseFieldDefinition::create('string')
      ->setLabel(t('From email'))
      ->setDisplayOptions('form', array(
        'type' => 'email',
      ))
      ->setDisplayConfigurable('form', TRUE);

    // Notification: subject.
    $fields['notification_subject'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Subject'))
      ->setDisplayOptions('form', array(
        'type' => 'email',
      ))
      ->setDisplayConfigurable('form', TRUE);

    // Notification: message.
    $fields['notification_message'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Message'))
      ->setSettings(array(
        'type' => 'string_textarea',
        'settings' => array(
          'rows' => 4,
        ),
      ))
      ->setDisplayOptions('form', array(
        'type' => 'string_textfield',
      ))
      ->setDisplayConfigurable('form', TRUE);

    return $fields;
  }

}
