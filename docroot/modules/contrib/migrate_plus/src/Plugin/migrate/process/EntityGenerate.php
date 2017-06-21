<?php

namespace Drupal\migrate_plus\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * This plugin generates entities within the process plugin.
 *
 * @MigrateProcessPlugin(
 *   id = "entity_generate"
 * )
 *
 * @see EntityLookup
 *
 * All the configuration from the lookup plugin applies here. In its most
 * simple form, this plugin needs no configuration. If there are fields on the
 * generated entity that are required or need some default value, that can be
 * provided via a default_values configuration option.
 *
 * Example usage with default_values configuration:
 * @code
 * destination:
 *   plugin: 'entity:node'
 * process:
 *   type:
 *     plugin: default_value
 *     default_value: page
 *   field_tags:
 *     plugin: entity_generate
 *     source: tags
 *     default_values:
 *       description: Default description
 *       field_long_description: Default long description
 * @endcode
 */
class EntityGenerate extends EntityLookup {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrateExecutable, Row $row, $destinationProperty) {
    // Creates an entity if the lookup determines it doesn't exist.
    if (!($result = parent::transform($value, $migrateExecutable, $row, $destinationProperty))) {
      $result = $this->generateEntity($value);
    }

    return $result;
  }

  /**
   * Generates an entity for a given value.
   *
   * @param string $value
   *   Value to use in creation of the entity.
   *
   * @return int|string
   *   The entity id of the generated entity.
   */
  protected function generateEntity($value) {
    if (!empty($value)) {
      $entity = $this->entityManager
        ->getStorage($this->lookupEntityType)
        ->create($this->entity($value));
      $entity->save();

      return $entity->id();
    }
  }

  /**
   * Fabricate an entity.
   *
   * This is intended to be extended by implementing classes to provide for more
   * dynamic default values, rather than just static ones.
   *
   * @param $value
   *   Primary value to use in creation of the entity.
   *
   * @return array
   *   Entity value array.
   */
  protected function entity($value) {
    $entity_values = [$this->lookupValueKey => $value];

    if ($this->lookupBundleKey) {
      $entity_values[$this->lookupBundleKey] = $this->lookupBundle;
    }

    // Gather any static default values for properties/fields.
    if (isset($this->configuration['default_values']) && is_array($this->configuration['default_values'])) {
      foreach ($this->configuration['default_values'] as $key => $value) {
        $entity_values[$key] = $value;
      }
    }

    return $entity_values;
  }

}
