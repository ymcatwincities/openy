<?php

namespace Drupal\Tests\metatag\Functional;

use Drupal\user\Entity\User;


/**
 * Misc helper functions for the automated tests.
 */
trait MetatagHelperTrait {

  /**
   * Log in as user 1.
   */
  protected function loginUser1() {
    // Log in as user 1.
    /* @var \Drupal\user\Entity\User $account */
    $account = User::load(1);
    $password = 'foo';
    $account->setPassword($password)->save();
    // Support old and new tests.
    $account->passRaw = $password;
    $account->pass_raw = $password;
    $this->drupalLogin($account);
  }

}
