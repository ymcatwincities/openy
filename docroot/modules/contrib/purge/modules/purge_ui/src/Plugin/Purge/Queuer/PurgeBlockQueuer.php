<?php

namespace Drupal\purge_ui\Plugin\Purge\Queuer;

use Drupal\purge\Plugin\Purge\Queuer\QueuerInterface;
use Drupal\purge\Plugin\Purge\Queuer\QueuerBase;

/**
 * Queuer for \Drupal\purge_ui\Form\PurgeBlockForm.
 *
 * @PurgeQueuer(
 *   id = "purge_ui_block_queuer",
 *   label = @Translation("Purge block(s)"),
 *   description = @Translation("Site builders can add 'purge this page' blocks to their block layout. Blocks configured to queue the items, will need this queuer."),
 *   enable_by_default = true,
 *   configform = "",
 * )
 */
class PurgeBlockQueuer extends QueuerBase implements QueuerInterface {

}
