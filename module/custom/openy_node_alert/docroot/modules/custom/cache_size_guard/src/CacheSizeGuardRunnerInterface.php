<?php

namespace Drupal\cache_size_guard;

/**
 * Interface CacheSizeGuardRunnerInterface.
 */
interface CacheSizeGuardRunnerInterface {

  /**
   * Run all or specific guard.
   *
   * @param string $guard
   *   Guard name.
   */
  public function run($guard = 'all');

}
