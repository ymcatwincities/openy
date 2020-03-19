<?php

namespace Drupal\activenet;

use GuzzleHttp\Client;

/**
 * Class ActivenetClient.
 *
 * @package Drupal\activenet
 *
 * @method mixed getCenters(array $args)
 * @method mixed getSites(array $args)
 * @method mixed getActivities(array $args)
 * @method mixed getActivityTypes(array $args)
 * @method mixed getActivityOtherCategories(array $args)
 * @method mixed getFlexRegPrograms(array $args)
 * @method mixed getFlexRegProgramTypes(array $args)
 * @method mixed getMembershipPackages(array $args)
 * @method mixed getMembershipCategories(array $args)
 * @method mixed getActivityDetail(int $id)
 */
class ActivenetClient extends Client implements ActivenetClientInterface {

  /**
    * Settings
    *
    * @var array of settings from config
   */
  protected $api_settings;

  /**
   * ActivenetClient constructor
   * @param array $api_settings
   *   The api config settings.
   */

  public function setApi(array $api_settings) {
    $this->api_settings = $api_settings;
  }



  /**
   * Wrapper for 'request' method.
   *
   * @param string $method
   *   HTTP Method.
   * @param string $uri
   *   ActiveNet URI.
   * @param array $parameters
   *   Arguments.
   *
   * @return mixed
   *   Data from ActiveNet.
   *
   * @throws \Drupal\activenet\ActivenetClientException
   */
  private function makeRequest($method, $uri, array $parameters = []) {
    try {
      $response = $this->request($method, $uri, $parameters);

      if (200 != $response->getStatusCode()) {
        throw new ActivenetClientException(sprintf('Got non 200 response code for the uri %s.', $uri));
      }

      if (!$body = $response->getBody()) {
        throw new ActivenetClientException(sprintf('Failed to get response body for the uri %s.', $uri));
      }

      if (!$contents = $body->getContents()) {
        throw new ActivenetClientException(sprintf('Failed to get body contents for the uri: %s.', $uri));
      }

      $object = json_decode($contents);

      // Check if object contains data.
      if (isset($object->body)) {
        return $object->body;
      }

      throw new ActivenetClientException(sprintf('Got unknown body name for method %s.', $method));
    }
    catch (\Exception $e) {
      throw new ActivenetClientException(sprintf('Failed to make a request for uri %s with message %s.', $uri, $e->getMessage()));
    }

  }

  /**
   * Magic call method.
   *
   * @param string $method
   *   Method.
   * @param mixed $args
   *   Arguments.
   *
   * @return mixed
   *   Data.
   *
   * @throws ActivenetClientException.
   */
  public function __call($method, $args) {
    if(!$this->api_settings) throw new ActivenetClientException(sprintf('Please inject api settings using "$this->setAPI($api_settings)".'));

    $api_key = $this->api_settings['api_key'];
    $base_uri = $this->api_settings['base_uri'];
    // Prepare suffix for the endpoint.
    $suffix = '';

    if (empty($args)) {
      $args[]['api_key'] = $api_key;
    }
    else {
      $args[0]['api_key'] = $api_key;
    }
    $suffix = '?' . http_build_query($args[0], '', '&');
    switch ($method) {
      case 'makeRequest':
        throw new ActivenetClientException(sprintf('Please, extend Activenet client!', $method));

      case 'getCenters':
        return $this->makeRequest('get', $base_uri . 'centers' . $suffix);

      case 'getSites':
        return $this->makeRequest('get', $base_uri . 'sites' . $suffix);

      case 'getActivities':
        return $this->makeRequest('get', $base_uri . 'activities' . $suffix);

      case 'getActivityTypes':
        return $this->makeRequest('get', $base_uri . 'activitytypes' . $suffix);

      case 'getActivityCategories':
        return $this->makeRequest('get', $base_uri . 'activitycategories' . $suffix);

      case 'getActivityOtherCategories':
        return $this->makeRequest('get', $base_uri . 'activityothercategories' . $suffix);

      case 'getFlexRegPrograms':
        return $this->makeRequest('get', $base_uri . 'flexregprograms' . $suffix);

      case 'getFlexRegProgramTypes':
        return $this->makeRequest('get', $base_uri . 'flexregprogramtypes' . $suffix);

      case 'getMembershipPackages':
        return $this->makeRequest('get', $base_uri . 'membershippackages' . $suffix);

      case 'getMembershipCategories':
        return $this->makeRequest('get', $base_uri . 'membershippackagecategories' . $suffix);
    }

    throw new ActivenetClientException(sprintf('Method %s not implemented yet.', $method));
  }

  public function getActivityDetail(int $id){
    if(!$this->api_settings) throw new ActivenetClientException(sprintf('Please inject api settings using "$this->setAPI($api_settings)".'));

    $base_uri = $this->api_settings['base_uri'];
    $suffix = '?api_key=' . $this->api_settings['api_key'];
    return $this->makeRequest('get', $base_uri . 'activities/' . $id . $suffix);
  }

}
