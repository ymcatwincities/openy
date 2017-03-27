<?php

namespace Drupal\ymca_retention;

/**
 * Provides an interface for regular updaters.
 */
interface RegularUpdaterInterface {

  /**
   * Checks if update is allowed.
   *
   * @param bool $allow_often
   *   Allow execute more often.
   *
   * @return bool
   *   TRUE if the update is allowed, FALSE otherwise.
   */
  public function isAllowed($allow_often = FALSE);

  /**
   * Create Queue.
   *
   * @param int $from
   *   Timestamp.
   * @param int $to
   *   Timestamp.
   */
  public function createQueue($from, $to);

}
