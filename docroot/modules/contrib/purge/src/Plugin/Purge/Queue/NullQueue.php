<?php

namespace Drupal\purge\Plugin\Purge\Queue;

use Drupal\purge\Plugin\Purge\Queue\QueueInterface;
use Drupal\purge\Plugin\Purge\Queue\MemoryQueue;

/**
 * API-compliant null queue back-end.
 *
 * This plugin is not intended for usage but gets loaded during module
 * installation, when configuration rendered invalid or when no other plugins
 * are available. Because its API compliant, Drupal won't crash visibly.
 *
 * @PurgeQueue(
 *   id = "null",
 *   label = @Translation("Null"),
 *   description = @Translation("API-compliant null queue back-end."),
 * )
 */
class NullQueue extends MemoryQueue implements QueueInterface {}
