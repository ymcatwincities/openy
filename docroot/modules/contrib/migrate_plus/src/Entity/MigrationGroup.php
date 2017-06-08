<?php

namespace Drupal\migrate_plus\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Migration Group entity.
 *
 * The migration group entity is used to group active migrations, as well as to
 * store shared migration configuration.
 *
 * @ConfigEntityType(
 *   id = "migration_group",
 *   label = @Translation("Migration Group"),
 *   module = "migrate_plus",
 *   handlers = {
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   }
 * )
 */
class MigrationGroup extends ConfigEntityBase implements MigrationGroupInterface {

  /**
   * The migration group ID (machine name).
   *
   * @var string
   */
  protected $id;

  /**
   * The human-readable label for the migration group.
   *
   * @var string
   */
  protected $label;

  /**
   * {@inheritdoc}
   */
  public function delete() {
    // Delete all migrations contained in this group.
    $query = \Drupal::entityQuery('migration')
      ->condition('migration_group', $this->id());
    $names = $query->execute();

    // Order the migrations according to their dependencies.
    /** @var MigrationInterface[] $migrations */
    $migrations = \Drupal::entityTypeManager()->getStorage('migration')->loadMultiple($names);

    // Delete in reverse order, so dependencies are never violated.
    $migrations = array_reverse($migrations);

    foreach ($migrations as $migration) {
      $migration->delete();
    }

    // Finally, delete the group itself.
    parent::delete();
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();
    // Make sure we save any explicit module dependencies.
    if ($provider = $this->get('module')) {
      $this->addDependency('module', $provider);
    }
    return $this->dependencies;
  }

}
