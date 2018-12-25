<?php

namespace Drupal\rabbit_hole;

use Drupal\Core\Config\ConfigFactory;
use Drupal\rabbit_hole\Entity\BehaviorSettings;

/**
 * Class BehaviorSettingsManager.
 *
 * @package Drupal\rabbit_hole
 */
class BehaviorSettingsManager implements BehaviorSettingsManagerInterface {

  /**
   * Drupal\Core\Config\ConfigFactory definition.
   *
   * @var Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Constructor.
   */
  public function __construct(ConfigFactory $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function saveBehaviorSettings($settings, $entity_type_id, $entity_id = NULL) {
    $id = $this->generateBehaviorSettingsFullId($entity_type_id, $entity_id);

    $entity = BehaviorSettings::load($id);
    if ($entity === NULL) {
      $entity_array = array('id' => $id);
      $entity_array += $settings;
      $entity = BehaviorSettings::create($entity_array);
    }
    else {
      foreach ($settings as $key => $setting) {
        $entity->set($key, $setting);
      }
    }
    $entity->save();
  }

  /**
   * {@inheritdoc}
   */
  public function loadBehaviorSettingsAsConfig($entity_type_id,
    $entity_id = NULL) {

    $actual = $this->configFactory->get(
      'rabbit_hole.behavior_settings.'
        . $this->generateBehaviorSettingsFullId($entity_type_id, $entity_id));
    if (!$actual->isNew()) {
      return $actual;
    }
    else {
      return $this->configFactory
        ->get('rabbit_hole.behavior_settings.default');
    }
  }

  /**
   * {@inheritdoc}
   */
  public function loadBehaviorSettingsAsEditableConfig($entity_type_id,
    $entity_id, $is_bundle = FALSE) {

    $actual = $this->configFactory->getEditable(
      'rabbit_hole.behavior_settings.'
        . $this->generateBehaviorSettingsFullId($entity_type_id, $entity_id,
            $is_bundle
      )
    );
    return !$actual->isNew() ? $actual : NULL;
  }

  /**
   * Generate a full ID based on entity type label, bundle label and entity id.
   *
   * @param string $entity_type_id
   *   The entity type (e.g. node) as a string.
   * @param string $entity_id
   *   The entity ID as a string.
   *
   * @return string
   *   The full id appropriate for a BehaviorSettings config entity.
   */
  private function generateBehaviorSettingsFullId($entity_type_id,
    $entity_id = '') {
    return $entity_type_id . (isset($entity_id) ? '_' . $entity_id : '');
  }

}
