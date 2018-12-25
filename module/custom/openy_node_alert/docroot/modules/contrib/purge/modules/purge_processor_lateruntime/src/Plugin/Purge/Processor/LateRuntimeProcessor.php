<?php

namespace Drupal\purge_processor_lateruntime\Plugin\Purge\Processor;

use Drupal\purge\Plugin\Purge\Processor\ProcessorInterface;
use Drupal\purge\Plugin\Purge\Processor\ProcessorBase;

/**
 * Late runtime processor.
 *
 * @PurgeProcessor(
 *   id = "lateruntime",
 *   label = @Translation("Late runtime processor"),
 *   description = @Translation("Process the queue on every request, this is only recommended on high latency configurations."),
 *   enable_by_default = true,
 *   configform = "",
 * )
 */
class LateRuntimeProcessor extends ProcessorBase implements ProcessorInterface {

}
