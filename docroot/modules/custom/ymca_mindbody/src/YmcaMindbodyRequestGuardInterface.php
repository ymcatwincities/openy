<?php

namespace Drupal\ymca_mindbody;

/**
 * Interface YmcaMindbodyRequestGuardInterface.
 *
 * @package Drupal\ymca_mindbody
 */
interface YmcaMindbodyRequestGuardInterface {

  /**
   * Checks current status.
   *
   * @return bool
   *   TRUE if requests to MindBody are available. FALSE if there are no allowed calls.
   */
  public function status();

  /**
   * Checks search criteria validity.
   *
   * @param array $criteria
   *   Associative array of search criteria.
   *
   * @return bool
   *   TRUE if the search criteria are valid, FALSE otherwise.
   */
  public function validateSearchCriteria(array $criteria);

}
