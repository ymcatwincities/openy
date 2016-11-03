<?php

namespace Drupal\daxko;

use GuzzleHttp\Client;

/**
 * Class DaxkoClient.
 *
 * @package Drupal\daxko
 */
class DaxkoClient extends Client {

  /**
   * {@inheritdoc}
   */
  public function getData($path) {
    try {
      $response = $this->get($path);
      if (200 != $response->getStatusCode()) {
        throw new DaxkoClientException(sprintf('Got non 200 response code for the path %s.', $path));
      }

      if (!$body = $response->getBody()) {
        throw new DaxkoClientException(sprintf('Failed to get response body for the path %s.', $path));
      }

      if (!$contents = $body->getContents()) {
        throw new DaxkoClientException(sprintf('Failed to get body contents for the path: %s.', $path));
      }

      $object = json_decode($contents);

      return $object->data;
    }
    catch (\Exception $e) {
      throw new DaxkoClientException(sprintf('Failed to make a request for path %s with message %s.', $path, $e->getMessage()));
    }

  }

}
