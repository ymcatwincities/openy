<?php

namespace Drupal\geolocation_dummy_geocoder\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\geolocation_dummy_geocoder\Plugin\geolocation\Geocoder\Dummy;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class DummyGeocoderController.
 */
class DummyGeocoderController extends ControllerBase {

  /**
   * Getgeocodedresults.
   *
   * @param string $address
   *   Address to geocode.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A JSON response containing autocomplete suggestions.
   */
  public function getGeocodedResults($address = '') {
    if (empty($address)) {
      return new JsonResponse([]);
    }

    if (empty(Dummy::$targets[$address])) {
      return new JsonResponse([]);
    }

    return new JsonResponse([
      'location' => Dummy::$targets[$address],
    ]);
  }

}
