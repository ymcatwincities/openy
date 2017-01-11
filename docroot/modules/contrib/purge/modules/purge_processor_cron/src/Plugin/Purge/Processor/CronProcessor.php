<?php

namespace Drupal\purge_processor_cron\Plugin\Purge\Processor;

use Drupal\purge\Plugin\Purge\Processor\ProcessorInterface;
use Drupal\purge\Plugin\Purge\Processor\ProcessorBase;

/**
 * Cron processor.
 *
 * @PurgeProcessor(
 *   id = "cron",
 *   label = @Translation("Cron processor"),
 *   description = @Translation("Processes the queue every time cron runs, recommended for most configurations."),
 *   enable_by_default = true,
 *   configform = "",
 * )
 */
class CronProcessor extends ProcessorBase implements ProcessorInterface {

}
