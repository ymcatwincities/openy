<?php

namespace Drupal\activenet;

use GuzzleHttp\Client;
// use Drupal\activenet\ActivenetClientFactory;

/**
 * Class ActivenetClient.
 *
 * @package Drupal\activenet
 *
 * @method mixed getBranches(array $args)
 * @method mixed getSessions(array $args)
 * @method mixed getPrograms(array $args)
 * @method mixed getChildCarePrograms(array $args)
 * @method mixed getMembershipTypes(array $args)
 */
class ActivenetClient extends Client implements ActivenetClientInterface {

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
    $settings = \Drupal::config('activenet.settings');
    $api_key = $settings->get('api_key');
    $base_uri = $settings->get('base_uri');

    // Prepare suffix for the endpoint.
    $suffix = '';  

    if (empty($args)) {
      $args[] = [
        'api_key' => $api_key
      ];
    }
    else {
      $args = ['api_key', $api_key];
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

      case 'getFlexRegPrograms':
        return $this->makeRequest('get', $base_uri . 'flexregprograms' . $suffix);

      case 'getMembershipPackages':
        return $this->makeRequest('get', $base_uri . 'membershippackages' . $suffix);

      case 'getMembershipCategories':
        return $this->makeRequest('get', $base_uri . 'membershippackagecategories' . $suffix);
    }

    throw new ActivenetClientException(sprintf('Method %s not implemented yet.', $method));
  }

}
