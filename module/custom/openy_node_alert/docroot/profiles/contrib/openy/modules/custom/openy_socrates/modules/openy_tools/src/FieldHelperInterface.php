<?php

namespace Drupal\openy_tools;

/**
 * Interface FieldHelperInterface.
 */
interface FieldHelperInterface {

  /**
   * Remove field from entity.
   *
   * @param string $entityTypeId
   *   Entity type ID.
   * @param array $bundles
   *   The list of bundles to remove field from.
   * @param string $fieldName
   *   Field name.
   * @param bool $backup
   *   Whether to make a copy of table with data.
   *
   * @return bool
   *   TRUE if case of success and FALSE in case of problem.
   */
  public function remove($entityTypeId, $bundles, $fieldName, $backup = TRUE);

}
