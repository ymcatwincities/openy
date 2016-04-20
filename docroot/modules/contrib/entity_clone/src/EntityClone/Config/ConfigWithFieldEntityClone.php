<?php

namespace Drupal\entity_clone\EntityClone\Config;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldConfigInterface;

/**
 * Class ContentEntityCloneBase.
 */
class ConfigWithFieldEntityClone extends ConfigEntityCloneBase {

  /**
   * {@inheritdoc}
   */
  public function cloneEntity(EntityInterface $entity, EntityInterface $cloned_entity, $properties = []) {
    $cloned_entity = parent::cloneEntity($entity, $cloned_entity, $properties);
    $bundle_of = $cloned_entity->getEntityType()->getBundleOf();
    if ($bundle_of) {
      $this->cloneFields($entity->id(), $cloned_entity->id(), $bundle_of);
    }

    $view_displays = \Drupal::service('entity_display.repository')->getViewModes($bundle_of);
    $view_displays = array_merge($view_displays, ['default' => 'default']);
    if (!empty($view_displays)) {
      $this->cloneDisplays('view', $entity->id(), $cloned_entity->id(), $view_displays, $bundle_of);
    }

    $view_displays = \Drupal::service('entity_display.repository')->getFormModes($bundle_of);
    $view_displays = array_merge($view_displays, ['default' => 'default']);
    if (!empty($view_displays)) {
      $this->cloneDisplays('form', $entity->id(), $cloned_entity->id(), $view_displays, $bundle_of);
    }

    return $cloned_entity;
  }

  /**
   * Clone all fields. Each field re-use existing field storage.
   *
   * @param string $entity_id
   *   The base entity ID.
   * @param $cloned_entity_id
   *   The cloned entity ID.
   * @param $bundle_of
   *   The bundle of the cloned entity.
   */
  protected function cloneFields($entity_id, $cloned_entity_id, $bundle_of) {
    /** @var \Drupal\Core\Entity\EntityFieldManager $field_manager */
    $field_manager = \Drupal::service('entity_field.manager');
    $fields = $field_manager->getFieldDefinitions($bundle_of, $entity_id);
    foreach ($fields as $field_id => $field_definition) {
      if ($field_definition instanceof FieldConfigInterface) {
        if ($this->entityTypeManager->hasHandler($this->entityTypeManager->getDefinition($field_definition->getEntityTypeId())
          ->id(), 'entity_clone')
        ) {
          /** @var \Drupal\entity_clone\EntityClone\EntityCloneInterface $field_config_clone_handler */
          $field_config_clone_handler = $this->entityTypeManager->getHandler($this->entityTypeManager->getDefinition($field_definition->getEntityTypeId())
            ->id(), 'entity_clone');
          $field_config_properties = [
            'id' => $field_definition->getName(),
            'label' => $field_definition->label(),
            'skip_storage' => TRUE,
          ];
          $cloned_field_definition = $field_definition->createDuplicate();
          $cloned_field_definition->set('bundle', $cloned_entity_id);
          $field_config_clone_handler->cloneEntity($field_definition, $cloned_field_definition, $field_config_properties);
        }
      }
    }
  }

  /**
   * Clone all fields. Each field re-use existing field storage.
   *
   * @param string $type
   *   The type of display (view or form).
   * @param string $entity_id
   *   The base entity ID.
   * @param $cloned_entity_id
   *   The cloned entity ID.
   * @param array $view_displays
   *   All view available display for this type.
   * @param $bundle_of
   *   The bundle of the cloned entity.
   */
  protected function cloneDisplays($type, $entity_id, $cloned_entity_id, $view_displays, $bundle_of) {
    foreach ($view_displays as $view_display_id => $view_display) {
      /** @var \Drupal\Core\Entity\Display\EntityDisplayInterface $display */
      $display = $this->entityTypeManager->getStorage('entity_' . $type . '_display')->load($bundle_of . '.' . $entity_id . '.' . $view_display_id);
      if ($display) {
        /** @var \Drupal\entity_clone\EntityClone\EntityCloneInterface $view_display_clone_handler */
        $view_display_clone_handler = $this->entityTypeManager->getHandler($this->entityTypeManager->getDefinition($display->getEntityTypeId())
          ->id(), 'entity_clone');
        $view_display_properties = [
          'id' => $bundle_of . '.' . $cloned_entity_id . '.' . $view_display_id,
        ];
        $cloned_view_display = $display->createDuplicate();
        $cloned_view_display->set('bundle', $cloned_entity_id);
        $view_display_clone_handler->cloneEntity($display, $cloned_view_display, $view_display_properties);
      }
    }
  }

}
