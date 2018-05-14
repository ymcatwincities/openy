<?php

/**
 * @file
 * Hooks provided by the geolocation module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Define additional parameters to the Google Maps API URL.
 *
 * @return array
 *   Parameters
 */
function hook_geolocation_google_maps_parameters() {
  return [
    'libraries' => [
      'places',
    ],
  ];
}

/**
 * @} End of "addtogroup hooks".
 */
