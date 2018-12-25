<?php

namespace Drupal\purge_purger_test\Plugin\Purge\Purger;

use Drupal\purge_purger_test\Plugin\Purge\Purger\NullPurgerBase;

/**
 * Test purger A.
 *
 * @PurgePurger(
 *   id = "a",
 *   label = @Translation("Purger A"),
 *   configform = "",
 *   cooldown_time = 0.2,
 *   description = @Translation("Test purger A."),
 *   multi_instance = FALSE,
 *   types = {"everything"},
 * )
 */
class APurger extends NullPurgerBase {}
