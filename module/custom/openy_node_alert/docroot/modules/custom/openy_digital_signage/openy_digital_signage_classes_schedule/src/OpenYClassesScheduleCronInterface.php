<?php

namespace Drupal\openy_digital_signage_classes_schedule;

/**
 * Provides an interface for regular updaters.
 *
 * @ingroup openy_digital_signage_classes_schedule
 */
interface OpenYClassesScheduleCronInterface {

  /**
   * Remove old sessions.
   */
  public function removeOldSessions();

}
