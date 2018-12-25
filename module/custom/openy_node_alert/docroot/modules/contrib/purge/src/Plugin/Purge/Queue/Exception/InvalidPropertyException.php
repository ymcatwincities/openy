<?php

namespace Drupal\purge\Plugin\Purge\Queue\Exception;

/**
 * Exception thrown when a data property on a ProxyItem object is called
 * that does not exist, e.g. $proxyitem->idontexist.
 */
class InvalidPropertyException extends \Exception {}
