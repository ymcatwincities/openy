<?php

namespace Drupal\daxko;

use GuzzleHttp\Client;

/**
 * Class DaxkoClient.
 *
 * @package Drupal\daxko
 *
 * @method mixed getBranches(array $args)
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

      // @todo Check if object contains data.
      return $object->data;
    }
    catch (\Exception $e) {
      throw new DaxkoClientException(sprintf('Failed to make a request for uri %s with message %s.', $uri, $e->getMessage()));
    }

  }

  /**
   * Magic call method.
   *
   * @param string $name
   *   Method.
   * @param array $args
   *   Arguments.
   *
   * @return mixed
   *   Data.
   *
   * @throws DaxkoClientException.
   */
  public function __call($name, array $args) {
    switch ($name) {
      case 'makeRequest':
        throw new DaxkoClientException(sprintf('Please, extend Daxko client!', $name));

      case 'getBranches':
        return $this->makeRequest('get', 'branches?' . http_build_query($args[0], NULL, NULL, PHP_QUERY_RFC3986));
    }

    throw new DaxkoClientException(sprintf('Method %s not implemented yet.', $name));
  }

}
