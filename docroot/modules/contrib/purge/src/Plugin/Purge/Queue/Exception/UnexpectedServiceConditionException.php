<?php

namespace Drupal\purge\Plugin\Purge\Queue\Exception;

/**
 * Exception thrown when the queue plugin is not acting in the way that was
 * expected to the QueueService. Usually when a item failed creation, etc.
 */
class UnexpectedServiceConditionException extends \Exception {}
