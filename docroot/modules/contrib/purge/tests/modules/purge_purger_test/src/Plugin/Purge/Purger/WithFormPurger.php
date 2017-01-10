<?php

namespace Drupal\purge_purger_test\Plugin\Purge\Purger;

use Drupal\purge_purger_test\Plugin\Purge\Purger\NullPurgerBase;

/**
 * Test PurgerWithForm.
 *
 * @PurgePurger(
 *   id = "withform",
 *   label = @Translation("Configurable purger"),
 *   configform = "\Drupal\purge_purger_test\Form\PurgerConfigForm",
 *   cooldown_time = 0.7,
 *   description = @Translation("Test purger with a form attached."),
 *   multi_instance = FALSE,
 *   types = {"path"},
 * )
 */
class WithFormPurger extends NullPurgerBase {}
