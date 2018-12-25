<?php

namespace Drupal\purge_processor_test\Plugin\Purge\Processor;

use Drupal\purge\Plugin\Purge\Processor\ProcessorInterface;
use Drupal\purge\Plugin\Purge\Processor\ProcessorBase;

/**
 * Test processor with a configuration form.
 *
 * @PurgeProcessor(
 *   id = "withform",
 *   label = @Translation("Processor with form"),
 *   description = @Translation("Test processor with a configuration form."),
 *   enable_by_default = false,
 *   configform = "\Drupal\purge_processor_test\Form\ProcessorConfigForm",
 * )
 */
class WithFormProcessor extends ProcessorBase implements ProcessorInterface {

}
