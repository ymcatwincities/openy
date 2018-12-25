<?php

namespace Drupal\rabbit_hole;

/**
 * Interface BehaviourSettingsManagerInterface.
 *
 * @package Drupal\rabbit_hole
 */
interface BehaviorSettingsManagerInterface {

  /**
   * Save behavior settings for an entity or bundle.
   *
   * @param array $settings
   *   The settings for the BehaviorSettings entity.
   * @param string $entity_type_id
   *   The entity type (e.g. node) as a string.
   * @param string $entity_id
   *   The entity ID as a string.
   */
  public function saveBehaviorSettings($settings, $entity_type_id, $entity_id);

  /**
   * Load behaviour settings for an entity or bundle, or load the defaults.
   *
   * Load rabbit hole behaviour settings appropriate to the given config or
   * default settings if not available.
   *
   * @param string $entity_type_label
   *   The entity type (e.g. node) as a string.
   * @param string $entity_id
   *   The entity ID as a string.
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   *   The BehaviorSettings Config object.
   */
  public function loadBehaviorSettingsAsConfig($entity_type_label, $entity_id);

  /**
   * Load behaviour settings for an entity or bundle, or return NULL.
   *
   * Load editable rabbit hole behaviour settings appropriate to the given
   * config or NULL if not available.
   *
   * @param string $entity_type_label
   *   The entity type (e.g. node) as a string.
   * @param string $entity_id
   *   The entity ID as a string.
   *
   * @return \Drupal\Core\Config\ImmutableConfig|null
   *   The BehaviorSettings Config object or NULL if it does not exist.
   */
  public function loadBehaviorSettingsAsEditableConfig($entity_type_label,
    $entity_id);

}
