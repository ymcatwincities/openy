<?php

namespace Drupal\purge_queuer_test\Plugin\Purge\Queuer;

use Drupal\purge\Plugin\Purge\Queuer\QueuerInterface;
use Drupal\purge\Plugin\Purge\Queuer\QueuerBase;

/**
 * Test queuer with a configuration form.
 *
 * @PurgeQueuer(
 *   id = "withform",
 *   label = @Translation("Queuer with form"),
 *   description = @Translation("Test queuer with a configuration form."),
 *   enable_by_default = false,
 *   configform = "\Drupal\purge_queuer_test\Form\QueuerConfigForm",
 * )
 */
class WithFormQueuer extends QueuerBase implements QueuerInterface {}
