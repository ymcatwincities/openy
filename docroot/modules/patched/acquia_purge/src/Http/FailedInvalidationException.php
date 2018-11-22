<?php

namespace Drupal\acquia_purge\Http;

use GuzzleHttp\Exception\BadResponseException;

/**
 * Thrown when a load balancer failed fulfilling the given invalidation request.
 */
class FailedInvalidationException extends BadResponseException {}