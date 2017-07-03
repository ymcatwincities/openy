<?php

namespace Drupal\scheduler\Exception;

/**
 * Defines an exception when the scheduled date is missing.
 *
 * This exception is thrown when Scheduler attempts to publish or unpublish a
 * node during cron but the date is missing.
 *
 * @see \Drupal\scheduler\SchedulerManager::publish()
 * @see \Drupal\scheduler\SchedulerManager::unpublish()
 */
class SchedulerMissingDateException extends \Exception {}
