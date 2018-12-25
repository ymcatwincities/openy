<?php

namespace Drupal\purge_purger_test\Plugin\Purge\Purger;

use Drupal\purge_purger_test\Plugin\Purge\Purger\NullPurgerBase;

/**
 * Test purger B.
 *
 * @PurgePurger(
 *   id = "b",
 *   label = @Translation("Purger B"),
 *   configform = "",
 *   cooldown_time = 0.5,
 *   description = @Translation("Test purger B."),
 *   multi_instance = FALSE,
 *   types = {"regex", "url"},
 * )
 */
class BPurger extends NullPurgerBase {}
