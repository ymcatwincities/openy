<?php

namespace Drupal\ymca_retention\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\ymca_retention\WinnerInterface;

/**
 * Defines the Member Activity entity.
 *
 * @ingroup ymca_retention
 *
 * @ContentEntityType(
 *   id = "ymca_retention_winner",
 *   label = @Translation("Winner entity"),
 *   handlers = {
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "ymca_retention_winner",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "id",
 *   },
 * )
 */
class Winner extends ContentEntityBase implements WinnerInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {

    // Standard field, used as unique if primary index.
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Winner entity.'))
      ->setReadOnly(TRUE);

    $fields['branch'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Branch ID'))
      ->setDescription(t('Member branch ID.'))
      ->setSettings([
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ]);

    $fields['member'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Member ID'))
      ->setDescription(t('The member ID of the winner.'))
      ->setSettings([
        'target_type' => 'ymca_retention_member',
        'default_value' => 0,
      ]);

    $fields['track'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nomination track'))
      ->setDescription(t('The name of the nomination track.'))
      ->setSettings([
        'default_value' => '',
        'max_length' => 255,
        'text_processing' => 0,
      ]);

    $fields['place'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Place'))
      ->setDescription(t('Place in the nomination track.'))
      ->setDefaultValue(0);

    return $fields;
  }

}
