<?php

/**
 * Alters Activity Finder Daxko API query params.
 *
 * @param array $params
 *   The array of query params for the Daxko search request.
 */
function hook_openy_daxko2_activity_finder_daxko_params_alter(array &$params) {
  $params['sort'] = 'ASC__score';
}
