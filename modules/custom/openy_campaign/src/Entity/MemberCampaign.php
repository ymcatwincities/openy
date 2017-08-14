<?php

namespace Drupal\openy_campaign\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\openy_campaign\MemberCampaignInterface;

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
 *     "label" = "campaign_id"
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
    $fields['campaign_id'] = BaseFieldDefinition::create('entity_reference')
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
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Member entity ID field.
    $fields['member_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Member ID'))
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
  public function getMemberId() {
    return $this->get('member_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setMemberId($member_id) {
    $this->set('member_id', $member_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCampaignId() {
    return $this->get('campaign_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setCampaignId($campaign_id) {
    $this->set('campaign_id', $campaign_id);
    return $this;
  }

}
