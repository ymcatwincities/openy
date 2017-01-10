<?php

namespace Drupal\purge_processor_test\Plugin\Purge\Processor;

use Drupal\purge\Plugin\Purge\Processor\ProcessorInterface;
use Drupal\purge\Plugin\Purge\Processor\ProcessorBase;

/**
 * Test processor A.
 *
 * @PurgeProcessor(
 *   id = "a",
 *   label = @Translation("Processor A"),
 *   description = @Translation("Test processor A."),
 *   enable_by_default = true,
 *   configform = "",
 * )
 */
class AProcessor extends ProcessorBase implements ProcessorInterface {

}
