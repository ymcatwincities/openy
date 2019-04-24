<?php

namespace Drupal\openy_campaign\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\openy_campaign\CampaignUtilizationActivityInterface;

/**
 * Defines the CampaignUtilizationActivity entity to store Utilization Activity.
 *
 * @ingroup openy_campaign
 *
 * @ContentEntityType(
 *   id = "openy_campaign_util_activity",
 *   label = @Translation("Campaign utilization activity entity"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\openy_campaign\Form\CampaignUtilizationActivityForm",
 *       "add" = "Drupal\openy_campaign\Form\CampaignUtilizationActivityForm",
 *       "edit" = "Drupal\openy_campaign\Form\CampaignUtilizationActivityForm",
 *       "delete" = "Drupal\openy_campaign\Form\CampaignUtilizationActivityDeleteForm",
 *     },
 *     "access" = "Drupal\openy_campaign\EntityAccess\MemberAccessControlHandler",
 *   },
 *   base_table = "openy_campaign_util_activity",
 *   admin_permission = "administer content types",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "campaign"
 *   },
 *   links = {
 *     "canonical" = "/admin/openy/retention-campaign/openy-campaign-utilization-activity/{openy_campaign_util_activity}",
 *     "edit-form" = "/admin/openy/retention-campaign/openy-campaign-utilization-activity/{openy_campaign_util_activity}/edit",
 *     "delete-form" = "/admin/openy/retention-campaign/openy-campaign-utilization-activity/{openy_campaign_util_activity}/delete",
 *     "collection" = "/admin/openy/retention-campaign/openy-campaign-utilization-activity/list"
 *   },
 * )
 */
class CampaignUtilizationActivitiy extends ContentEntityBase implements CampaignUtilizationActivityInterface {

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
      ->setDescription(t('The ID of the CampaignUtilizationActivity entity.'))
      ->setReadOnly(TRUE);

    // Campaign entity ID field.
    $fields['member_campaign'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Campaign ID'))
      ->setDescription(t('The id of the Member_Campaign entity. Start typing member campaign name.'))
      ->setSetting('target_type', 'node')
      ->setSetting('handler', 'default')
      ->setSetting('handler_settings', ['target_bundles' => ['member_campaign' => 'member_campaign']])
      ->setDisplayOptions('view', [
        'label'  => 'hidden',
        'type'   => 'member_campaign',
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

    $fields['activity_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Activity Type'))
      ->setDescription(t('The Activity type.'));

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
  public function getMemberCampaign() {
    return $this->get('member_campaign')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setMemberCampaign(MemberCampaign $memberCampaign) {
    $this->set('member_campaign', $memberCampaign);
    return $this;
  }

  /**
   * @return mixed
   */
  public function getCreated() {
    return $this->get('created')->value;
  }

  /**
   * @param $created
   *
   * @return $this
   */
  public function setCreated($created) {
    $this->set('created', $created);
    return $this;
  }

  /**
   * @return mixed
   */
  public function getActivityType() {
    return $this->get('activity_type')->value;
  }

  /**
   * @param $activityType
   *
   * @return $this
   */
  public function setActivityType($activityType) {
    $this->set('activity_type', $activityType);
    return $this;
  }

}
