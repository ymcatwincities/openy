<?php

namespace Drupal\rabbit_hole;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\rabbit_hole\Plugin\RabbitHoleBehaviorPluginManager;
use Drupal\rabbit_hole\Plugin\RabbitHoleEntityPluginManager;

/**
 * Class EntityExtender.
 *
 * @package Drupal\rabbit_hole
 */
class EntityExtender implements EntityExtenderInterface {
  use StringTranslationTrait;

  /**
   * Drupal\rabbit_hole\Plugin\RabbitHoleBehaviorPluginManager definition.
   *
   * @var Drupal\rabbit_hole\Plugin\RabbitHoleBehaviorPluginManager
   */
  protected $rhBehaviorPluginManager;

  /**
   * Drupal\rabbit_hole\Plugin\RabbitHoleEntityPluginManager definition.
   *
   * @var Drupal\rabbit_hole\Plugin\RabbitHoleEntityPluginManager
   */
  protected $rhEntityPluginManager;

  /**
   * Constructor.
   */
  public function __construct(RabbitHoleBehaviorPluginManager $plugin_manager_rabbit_hole_behavior_plugin,
    RabbitHoleEntityPluginManager $plugin_manager_rabbit_hole_entity_plugin) {
    $this->rhBehaviorPluginManager = $plugin_manager_rabbit_hole_behavior_plugin;
    $this->rhEntityPluginManager = $plugin_manager_rabbit_hole_entity_plugin;
  }

  /**
   * Return fields added by rabbit hole for use in entity_base_field_info hooks.
   *
   * @param string $entity_type_id
   *   The string ID of the entity type.
   *
   * @return array
   *   An array of general extra fields.
   */
  public function getRabbitHoleFields($entity_type_id) {
    $entity_types = $this->rhEntityPluginManager->loadSupportedEntityTypes();
    if (in_array($entity_type_id, $entity_types)) {
      return $this->getGeneralExtraFields();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getGeneralExtraFields() {
    $fields = array();
    $fields['rh_action'] = BaseFieldDefinition::create('string')
      ->setName('rh_action')
      ->setLabel($this->t('Rabbit Hole action'))
      ->setDescription($this->t('Specifies which action that Rabbit Hole should take.'));
    foreach ($this->rhBehaviorPluginManager->getDefinitions() as $id => $def) {
      $this->rhBehaviorPluginManager
        ->createInstance($id)
        ->alterExtraFields($fields);
    }
    return $fields;
  }

}
