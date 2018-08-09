<?php

namespace Drupal\openy_prgf_class_location;

/**
 * Class ClassLocationService.
 *
 * @package Drupal\openy_prgf_class_location
 */
interface ClassLocationServiceInterface {

  /**
   * Retrieves location node for the location parameter value.
   *
   * @param $location_id string
   *   Location id.
   *
   * @return \Drupal\node\NodeInterface|null
   *   Location node or NULL.
   */
  public function getLocationNode($location_id);

}
