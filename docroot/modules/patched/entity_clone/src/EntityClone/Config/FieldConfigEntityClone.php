<?php

namespace Drupal\entity_clone\EntityClone\Config;

use Drupal\Core\Entity\EntityInterface;

/**
 * Class FieldConfigEntityClone.
 */
class FieldConfigEntityClone extends ConfigEntityCloneBase {

  /**
   * {@inheritdoc}
   */
  public function cloneEntity(EntityInterface $field_config, EntityInterface $cloned_field_config, $properties = []) {
    /** @var \Drupal\field\Entity\FieldConfig $field_config */
    /** @var \Drupal\field\Entity\FieldConfig $cloned_field_config */
    /** @var \Drupal\field\Entity\FieldStorageConfig $cloned_field_storage */

    if ((!isset($properties['skip_storage']) || !$properties['skip_storage'])) {
      $cloned_field_storage = $field_config->getFieldStorageDefinition()->createDuplicate();
      $cloned_field_storage->set('field_name', $properties['id']);
      $cloned_field_storage->set('id', $properties['id'] . '.' . $cloned_field_storage->getTargetEntityTypeId());
      $cloned_field_storage->save();
    }
    unset($properties['skip_storage']);

    $properties['field_name'] = $properties['id'];
    $properties['id'] = $cloned_field_config->getTargetEntityTypeId() . '.' . $cloned_field_config->getTargetBundle() . '.' . $properties['id'];
    return parent::cloneEntity($field_config, $cloned_field_config, $properties);
  }

}
