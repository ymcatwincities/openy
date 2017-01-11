<?php

namespace Drupal\purge\Plugin\Purge\Purger\Exception;

/**
 * Thrown when ::isSystemOnFire() of the diagnostics service reported a
 * SEVERITY_ERROR level issue, this forces all purging to be halted.
 *
 * @see \Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface::invalidate().
 */
class DiagnosticsException extends \Exception {}
