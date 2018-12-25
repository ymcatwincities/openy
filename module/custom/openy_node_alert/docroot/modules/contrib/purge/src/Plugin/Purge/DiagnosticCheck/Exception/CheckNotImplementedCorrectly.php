<?php

namespace Drupal\purge\Plugin\Purge\DiagnosticCheck\Exception;

/**
 * Thrown when \Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticCheckInterface::run is not
 * returning a severity integer as mandated by the API.
 */
class CheckNotImplementedCorrectly extends \Exception {}
