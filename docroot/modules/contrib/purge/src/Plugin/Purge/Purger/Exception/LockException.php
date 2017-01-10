<?php

namespace Drupal\purge\Plugin\Purge\Purger\Exception;

/**
 * Thrown when processing is attempted while another instance is running.
 */
class LockException extends \Exception {}
