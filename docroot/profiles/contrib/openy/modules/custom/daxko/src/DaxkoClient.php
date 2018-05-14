<?php

namespace Drupal\daxko;

use GuzzleHttp\Client;

/**
 * Class DaxkoClient.
 *
 * @package Drupal\daxko
 *
 * @method mixed getBranches(array $args)
 * @method mixed getSessions(array $args)
 * @method mixed getPrograms(array $args)
 * @method mixed getChildCarePrograms(array $args)
 * @method mixed getMembershipTypes(array $args)
 */
class DaxkoClient extends Client implements DaxkoClientInterface {

  /**
   * Wrapper for 'request' method.
   *
   * @param string $method
   *   HTTP Method.
   * @param string $uri
   *   Daxko URI.
   * @param array $parameters
   *   Arguments.
   *
   * @return mixed
   *   Data from Daxko.
   *
   * @throws \Drupal\daxko\DaxkoClientException
   */
  private function makeRequest($method, $uri, array $parameters = []) {
    try {
      $response = $this->request($method, $uri, $parameters);
      if (200 != $response->getStatusCode()) {
        throw new DaxkoClientException(sprintf('Got non 200 response code for the uri %s.', $uri));
      }

      if (!$body = $response->getBody()) {
        throw new DaxkoClientException(sprintf('Failed to get response body for the uri %s.', $uri));
      }

      if (!$contents = $body->getContents()) {
        throw new DaxkoClientException(sprintf('Failed to get body contents for the uri: %s.', $uri));
      }

      $object = json_decode($contents);

      // todo remove todo??? @todo Check if object contains data.
      if (isset($object->data)) {
        return $object->data;
      }
      elseif (isset($object->tags)) {
        return $object->tags;
      }

      throw new DaxkoClientException(sprintf('Got unknown body name for method %s.', $method));
    }
    catch (\Exception $e) {
      throw new DaxkoClientException(sprintf('Failed to make a request for uri %s with message %s.', $uri, $e->getMessage()));
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
   * @throws DaxkoClientException.
   */
  public function __call($method, $args) {
    // Prepare suffix for the endpoint.
    $suffix = '';
    if (!empty($args[0])) {
      $suffix = '?' . http_build_query($args[0], '', '&');
    }

    switch ($method) {
      case 'makeRequest':
        throw new DaxkoClientException(sprintf('Please, extend Daxko client!', $method));

      case 'getBranches':
        return $this->makeRequest('get', 'branches' . $suffix);

      case 'getSessions':
        return $this->makeRequest('get', 'sessions' . $suffix);

      case 'getPrograms':
        return $this->makeRequest('get', 'programs' . $suffix);

      case 'getChildCarePrograms':
        return $this->makeRequest('get', 'childcare/programs' . $suffix);

      case 'getMembershipTypes':
        return $this->makeRequest('get', 'membershiptypes' . $suffix);
    }

    throw new DaxkoClientException(sprintf('Method %s not implemented yet.', $method));
  }

}
