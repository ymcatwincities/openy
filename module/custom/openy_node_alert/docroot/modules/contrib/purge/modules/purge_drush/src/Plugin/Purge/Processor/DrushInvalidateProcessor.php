<?php

namespace Drupal\purge_drush\Plugin\Purge\Processor;

use Drupal\purge\Plugin\Purge\Processor\ProcessorInterface;
use Drupal\purge\Plugin\Purge\Processor\ProcessorBase;

/**
 * Processor for the 'drush p-invalidate' command.
 *
 * @PurgeProcessor(
 *   id = "drush_purge_invalidate",
 *   label = @Translation("Drush p-invalidate"),
 *   description = @Translation("Processor for the 'drush p-invalidate' command."),
 *   enable_by_default = true,
 *   configform = "",
 * )
 */
class DrushInvalidateProcessor extends ProcessorBase implements ProcessorInterface {

}
