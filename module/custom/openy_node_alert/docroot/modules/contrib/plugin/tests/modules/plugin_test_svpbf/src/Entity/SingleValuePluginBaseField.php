<?php

namespace Drupal\plugin_test_svpbf\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines an entity with a single-value plugin base field.
 *
 * @ContentEntityType(
 *   base_table = "plugin_test_svpbf",
 *   handlers = {
 *     "storage" = "Drupal\Core\Entity\Sql\SqlContentEntityStorage",
 *     "views_data" = "Drupal\views\EntityViewsData"
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   },
 *   id = "plugin_test_svpbf",
 *   label = @Translation("Single-value value plugin base field")
 * )
 */
class SingleValuePluginBaseField extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields['plugin'] = BaseFieldDefinition::create('plugin:plugin_test_helper_mock')
      ->setLabel(t('Plugin'))
      ->setDisplayOptions('view', array(
        'type' => 'plugin_label',
        'weight' => 0,
      ))
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
