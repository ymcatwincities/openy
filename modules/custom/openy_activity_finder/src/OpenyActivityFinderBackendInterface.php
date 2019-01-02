<?php

namespace Drupal\openy_activity_finder;

interface OpenyActivityFinderBackendInterface {

  /**
   * Run Programs search.
   *
   * @param $parameters
   *   GET parameters for the search.
   * @param $log_id
   *   Id of the Search Log needed for tracking Register / Details actions.
   */
  public function runProgramSearch($parameters, $log_id);

  /**
   * Get list of all locations for filters.
   */
  public function getLocations();

}