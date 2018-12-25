<?php

namespace Drupal\ymca_migrate_landing_page;

use Drupal\Core\Entity\EntityInterface;

/**
 * Interface MigrationImporterInterface.
 */
interface MigrationImporterInterface {

  /**
   * Migrate node.
   *
   * @param \Drupal\Core\Entity\EntityInterface $node
   *   Node to be migrated.
   */
  public static function migrate(EntityInterface $node);

}
