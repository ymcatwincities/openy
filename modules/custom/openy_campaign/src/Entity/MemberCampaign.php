<?php

namespace Drupal\openy_campaign\Entity;

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
     * To test generate Members, create MemberCampaign record and run code in /deve/php
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

    $client = \Drupal::getContainer()->get('openy_campaign.client_factory')->getClient();

    $results = $client->getVisitCountByDate($memberId, $from, $to);
    dpm($results);
    return;

    $visitsNumber = 1; // This should come from API's call.

    $maxGoal = 5; // Add field to Campaign node and use that value.

    $weeksSpan = ceil($from_date->diff($to_date)->days / 7);
    $calculatedGoal = ceil(2 * $visitsNumber / $weeksSpan) + 1;

    $goal = min($calculatedGoal, $maxGoal);

    $this->setGoal($goal);
  }

}
