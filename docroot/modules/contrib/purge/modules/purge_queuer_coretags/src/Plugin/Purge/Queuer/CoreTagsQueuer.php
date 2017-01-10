<?php

namespace Drupal\purge_queuer_coretags\Plugin\Purge\Queuer;

use Drupal\purge\Plugin\Purge\Queuer\QueuerInterface;
use Drupal\purge\Plugin\Purge\Queuer\QueuerBase;

/**
 * Queues every tag that Drupal invalidates internally.
 *
 * @PurgeQueuer(
 *   id = "coretags",
 *   label = @Translation("Core tags queuer"),
 *   description = @Translation("Queues every tag that Drupal invalidates internally."),
 *   enable_by_default = true,
 *   configform = "\Drupal\purge_queuer_coretags\Form\ConfigurationForm",
 * )
 */
class CoreTagsQueuer extends QueuerBase implements QueuerInterface {

}
