<?php

namespace Drupal\daxko;

use GuzzleHttp\Client;

/**
 * Class DaxkoClient.
 *
 * @package Drupal\daxko
 */
class DaxkoClient extends Client implements DaxkoClientInterface {

  /**
   * {@inheritdoc}
   */
  public function makeRequest($method, $uri, array $parameters = []) {
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

}
