<?php

namespace Drupal\ymca_retention;

/**
 * Provides an interface for regular updaters.
 */
interface RegularUpdaterInterface {

  /**
   * Checks if update is allowed.
   *
   * @return bool
   *   TRUE if the update is allowed, FALSE otherwise.
   */
  public function isAllowed();

  /**
   * Runs update.
   */
  public function runUpdate();

}
