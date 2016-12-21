<?php

namespace Drupal\purge_ui\Plugin\Purge\Processor;

use Drupal\purge\Plugin\Purge\Processor\ProcessorInterface;
use Drupal\purge\Plugin\Purge\Processor\ProcessorBase;

/**
 * Processor for \Drupal\purge_ui\Form\PurgeBlockForm.
 *
 * @PurgeProcessor(
 *   id = "purge_ui_block_processor",
 *   label = @Translation("Purge block(s)"),
 *   description = @Translation("Site builders can add 'purge this page' blocks to their block layout. Blocks configured to perform direct execution, will need this processor."),
 *   enable_by_default = true,
 *   configform = "",
 * )
 */
class PurgeBlockProcessor extends ProcessorBase implements ProcessorInterface {

}
