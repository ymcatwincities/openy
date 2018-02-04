<?php

namespace Drupal\openy_campaign\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the Winner entity.
 *
 * @ingroup openy_campaign
 *
 * @ContentEntityType(
 *   id = "openy_campaign_winner",
 *   label = @Translation("Winner entity"),
 *   handlers = {
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "openy_campaign_winner",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "id",
 *   },
 * )
 */
class Winner extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    // Standard field, used as unique if primary index.
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Winner entity.'))
      ->setReadOnly(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the record was created.'));

    $fields['member_campaign'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Member Campaign winner'))
      ->setDescription(t('Member Campaign winner'))
      ->setSettings([
        'target_type' => 'openy_campaign_member_campaign',
        'default_value' => 0,
      ]);

    $fields['activity'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Activity'))
      ->setDescription(t('The activity of the winner.'))
      ->setSettings([
        'target_type' => 'taxonomy_term',
        'default_value' => 0,
      ]);

    $fields['place'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Place'))
      ->setDescription(t('Place in the nomination track.'))
      ->setDefaultValue(0);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getMemberCampaignId() {
    return $this->get('member_campaign')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getBranchId() {
    return $this->get('activity')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getPlace() {
    return $this->get('place')->value;
  }

}
