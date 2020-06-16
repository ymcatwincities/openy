<?php

namespace Drupal\openy_migrate\Plugin\migrate\process;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\migrate\MigrateSkipProcessException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Skips processing the current row when the bundle not exist.
 *
 * Available configuration keys:
 * - method: (required) What to do if the input value is empty. Possible values:
 *   - row: Skips the entire row when bundle not exist.
 *   - process: Prevents further processing of the input property
 *     when bundle not exist.
 * - entity: (required) Entity machine name.
 * - bundle: (required) Bundle machine name.
 *
 * Examples:
 *
 * @code
 * process:
 *   type:
 *     plugin: skip_if_bundle_not_exist
 *     method: row
 *     entity: paragraph
 *     bundle: demo
 * @endcode
 *
 * If field_name is empty, skips the entire row and the message 'Field
 * field_name is missed' is logged in the message table.
 *
 * @code
 * process:
 *   parent:
 *     -
 *       plugin: skip_if_bundle_not_exist
 *       method: process
 *       entity: node
 *       bundle: article
 *     -
 *       plugin: migration
 *       migration: custom_node_migration
 * @endcode
 *
 * When article bundle not exist for node, any further processing of the
 * property is skipped, the next plugin (migration) will not be run.
 *
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 *
 * @MigrateProcessPlugin(
 *   id = "skip_if_bundle_not_exist"
 * )
 */
class SkipIfBundleNotExist extends ProcessPluginBase implements ContainerFactoryPluginInterface {


  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityManager;

  /**
   * The current migration.
   *
   * @var \Drupal\migrate\Plugin\MigrationInterface
   */
  protected $migration;

  /**
   * Entity machine name.
   *
   * @var string
   */
  protected $entityName;

  /**
   * Bundle machine name.
   *
   * @var string
   */
  protected $entityBundle;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, EntityTypeManagerInterface $entity_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->migration = $migration;
    $this->entityManager = $entity_manager;
    $this->entityName = $configuration['entity'];
    $this->entityBundle = $configuration['bundle'];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('entity_type.manager')
    );
  }

  /**
   * Skips the current row when bundle not exist.
   *
   * @param mixed $value
   *   The input value.
   * @param \Drupal\migrate\MigrateExecutableInterface $migrate_executable
   *   The migration in which this process is being executed.
   * @param \Drupal\migrate\Row $row
   *   The row from the source to process.
   * @param string $destination_property
   *   The destination property currently worked on. This is only used together
   *   with the $row above.
   *
   * @return string
   *   Entity bundle name if exist.
   *
   * @throws \Drupal\migrate\MigrateSkipRowException
   *   Thrown if entity bundle not exist and the row should be skipped,
   *   records with STATUS_IGNORED status in the map.
   */
  public function row($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    //$bundles = $this->entityManager->getBundleInfo($this->entityName);
    $bundles = \Drupal::service('entity_type.bundle.info')->getBundleInfo($this->entityName);
    if (!isset($bundles[$this->entityBundle])) {
      throw new MigrateSkipRowException();
    }
    return $this->configuration['bundle'];
  }

  /**
   * Stops processing the current property when bundle not exist.
   *
   * @param mixed $value
   *   The input value.
   * @param \Drupal\migrate\MigrateExecutableInterface $migrate_executable
   *   The migration in which this process is being executed.
   * @param \Drupal\migrate\Row $row
   *   The row from the source to process.
   * @param string $destination_property
   *   The destination property currently worked on. This is only used together
   *   with the $row above.
   *
   * @return string
   *   Entity bundle name if exist.
   *
   * @throws \Drupal\migrate\MigrateSkipProcessException
   *   Thrown if entity bundle not exist and rest of the process should
   *   be skipped.
   */
  public function process($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    //$bundles = $this->entityManager->getBundleInfo($this->entityName);
    $bundles = \Drupal::service('entity_type.bundle.info')->getBundleInfo($this->entityName);
    if (!isset($bundles[$this->entityBundle])) {
      throw new MigrateSkipProcessException();
    }
    return $this->configuration['bundle'];
  }

}
