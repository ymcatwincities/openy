<?php

namespace Drupal\purge_drush\Plugin\Purge\Processor;

use Drupal\purge\Plugin\Purge\Processor\ProcessorInterface;
use Drupal\purge\Plugin\Purge\Processor\ProcessorBase;

/**
 * Processor for the 'drush p-queue-work' command.
 *
 * @PurgeProcessor(
 *   id = "drush_purge_queue_work",
 *   label = @Translation("Drush p-queue-work"),
 *   description = @Translation("Processor for the 'drush p-queue-work' command."),
 *   enable_by_default = true,
 *   configform = "",
 * )
 */
class DrushQueueWorkProcessor extends ProcessorBase implements ProcessorInterface {

}
