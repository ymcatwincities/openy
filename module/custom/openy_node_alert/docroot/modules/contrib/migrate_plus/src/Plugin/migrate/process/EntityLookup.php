<?php

namespace Drupal\migrate_plus\Plugin\migrate\process;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * This plugin looks for existing entities.
 *
 * @MigrateProcessPlugin(
 *   id = "entity_lookup",
 *   handle_multiples = TRUE
 * )
 *
 * In its most simple form, this plugin needs no configuration. However, if the
 * lookup properties cannot be determined through introspection, define them via
 * configuration.
 *
 * Example usage with minimal configuration:
 * @code
 * destination:
 *   plugin: 'entity:node'
 * process:
 *   type:
 *     plugin: default_value
 *     default_value: page
 *   field_tags:
 *     plugin: entity_lookup
 *     source: tags
 * @endcode
 *
 * Example usage with full configuration:
 * @code
 *   field_tags:
 *     plugin: entity_lookup
 *     source: tags
 *     value_key: name
 *     bundle_key: vid
 *     bundle: tags
 *     entity_type: taxonomy_term
 *     ignore_case: true
 * @endcode
 */
class EntityLookup extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /** @var \Drupal\Core\Entity\EntityManagerInterface */
  protected $entityManager;

  /** @var \Drupal\migrate\Plugin\MigrationInterface */
  protected $migration;

  /** @var \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManagerInterface */
  protected $selectionPluginManager;

  /** @var string */
  protected $destinationEntityType;

  /** @var string|bool */
  protected $destinationBundleKey;

  /** @var string */
  protected $lookupValueKey;

  /** @var string */
  protected $lookupBundleKey;

  /** @var string */
  protected $lookupBundle;

  /** @var string */
  protected $lookupEntityType;

  /** @var string */
  protected $destinationProperty;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $pluginId, $pluginDefinition, MigrationInterface $migration, EntityManagerInterface $entityManager, SelectionPluginManagerInterface $selectionPluginManager) {
    parent::__construct($configuration, $pluginId, $pluginDefinition);
    $this->migration = $migration;
    $this->entityManager = $entityManager;
    $this->selectionPluginManager = $selectionPluginManager;
    $pluginIdParts = explode(':', $this->migration->getDestinationPlugin()->getPluginId());
    $this->destinationEntityType = empty($pluginIdParts[1]) ?: $pluginIdParts[1];
    $this->destinationBundleKey = !$this->destinationEntityType ?: $this->entityManager->getDefinition($this->destinationEntityType)->getKey('bundle');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $pluginId, $pluginDefinition, MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $pluginId,
      $pluginDefinition,
      $migration,
      $container->get('entity.manager'),
      $container->get('plugin.manager.entity_reference_selection')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrateExecutable, Row $row, $destinationProperty) {
    // In case of subfields ('field_reference/target_id'), extract the field
    // name only.
    $parts = explode('/', $destinationProperty);
    $destinationProperty = reset($parts);
    $this->determineLookupProperties($destinationProperty);

    $this->destinationProperty = isset($this->configuration['destination_field']) ? $this->configuration['destination_field'] : NULL;

    return $this->query($value);
  }

  /**
   * Determine the lookup properties from config or target field configuration.
   *
   * @param string $destinationProperty
   *   The destination property currently worked on. This is only used together
   *   with the $row above.
   */
  protected function determineLookupProperties($destinationProperty) {
    if (!empty($this->configuration['value_key'])) {
      $this->lookupValueKey = $this->configuration['value_key'];
    }
    if (!empty($this->configuration['bundle_key'])) {
      $this->lookupBundleKey = $this->configuration['bundle_key'];
    }
    if (!empty($this->configuration['bundle'])) {
      $this->lookupBundle = $this->configuration['bundle'];
    }
    if (!empty($this->configuration['entity_type'])) {
      $this->lookupEntityType = $this->configuration['entity_type'];
    }

    if (empty($this->lookupValueKey) || empty($this->lookupBundleKey) || empty($this->lookupBundle) || empty($this->lookupEntityType)) {
      // See if we can introspect the lookup properties from the destination field.
      if (!empty($this->migration->getProcess()[$this->destinationBundleKey][0]['default_value'])) {
        $destinationEntityBundle = $this->migration->getProcess()[$this->destinationBundleKey][0]['default_value'];
        $fieldConfig = $this->entityManager->getFieldDefinitions($this->destinationEntityType, $destinationEntityBundle)[$destinationProperty]->getConfig($destinationEntityBundle);
        switch ($fieldConfig->getType()) {
          case 'entity_reference':
            if (empty($this->lookupBundle)) {
              $handlerSettings = $fieldConfig->getSetting('handler_settings');
              $bundles = array_filter((array) $handlerSettings['target_bundles']);
              if (count($bundles) == 1) {
                $this->lookupBundle = reset($bundles);
              }
              // This was added in 8.1.x is not supported in 8.0.x.
              elseif (!empty($handlerSettings['auto_create']) && !empty($handlerSettings['auto_create_bundle'])) {
                $this->lookupBundle = reset($handlerSettings['auto_create_bundle']);
              }
            }

            // Make an assumption that if the selection handler can target more than
            // one type of entity that we will use the first entity type.
            $this->lookupEntityType = $this->lookupEntityType ?: reset($this->selectionPluginManager->createInstance($fieldConfig->getSetting('handler'))->getPluginDefinition()['entity_types']);
            $this->lookupValueKey = $this->lookupValueKey ?: $this->entityManager->getDefinition($this->lookupEntityType)->getKey('label');
            $this->lookupBundleKey = $this->lookupBundleKey ?: $this->entityManager->getDefinition($this->lookupEntityType)->getKey('bundle');
            break;

          case 'file':
          case 'image':
            $this->lookupEntityType = 'file';
            $this->lookupValueKey = $this->lookupValueKey ?: 'uri';
            break;

          default:
            throw new MigrateException('Destination field type ' .
              $fieldConfig->getType(). 'is not a recognized reference type.');
        }
      }
    }

    // If there aren't enough lookup properties available by now, then bail.
    if (empty($this->lookupValueKey)) {
      throw new MigrateException('The entity_lookup plugin requires a value_key, none located.');
    }
    if (!empty($this->lookupBundleKey) && empty($this->lookupBundle)) {
      throw new MigrateException('The entity_lookup plugin found no bundle but destination entity requires one.');
    }
    if (empty($this->lookupEntityType)) {
      throw new MigrateException('The entity_lookup plugin requires a entity_type, none located.');
    }
  }

  /**
   * Checks for the existence of some value.
   *
   * @param $value
   * The value to query.
   *
   * @return mixed|null
   *   Entity id if the queried entity exists. Otherwise NULL.
   */
  protected function query($value) {
    // Entity queries typically are case-insensitive. Therefore, we need to
    // handle case sensitive filtering as a post-query step. By default, it
    // filters case insensitive. Change to true if that is not the desired
    // outcome.
    $ignoreCase = !empty($this->configuration['ignore_case']) ?: FALSE;

    $multiple = is_array($value);

    $query = $this->entityManager->getStorage($this->lookupEntityType)
      ->getQuery()
      ->condition($this->lookupValueKey, $value, $multiple ? 'IN' : NULL);

    if ($this->lookupBundleKey) {
      $query->condition($this->lookupBundleKey, $this->lookupBundle);
    }
    $results = $query->execute();

    if (empty($results)) {
      return NULL;
    }

    // By default do a case-sensitive comparison.
    if (!$ignoreCase) {
      // Returns the entity's identifier.
      foreach ($results as $k => $identifier) {
        $result_value = $this->entityManager->getStorage($this->lookupEntityType)->load($identifier)->{$this->lookupValueKey}->value;
        if (($multiple && !in_array($result_value, $value, TRUE)) || (!$multiple && $result_value !== $value)) {
          unset($results[$k]);
        }
      }
    }

    if ($multiple && !empty($this->destinationProperty)) {
      array_walk($results, function (&$value) {
        $value = [$this->destinationProperty => $value];
      });
    }

    return $multiple ? array_values($results) : reset($results);
  }

}
