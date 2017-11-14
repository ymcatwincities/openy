<?php

namespace Drupal\openy_digital_signage_classes_schedule;

/**
 * Provides an interface for cron methods to import data from 3rd party services.
 *
 * @ingroup openy_digital_signage_classes_schedule
 */
interface OpenYSessionsCronImporterInterface {

  /**
   * Checks if import is allowed.
   *
   * @param bool $allow_often
   *   Allow execute more often.
   *
   * @return bool
   *   TRUE if the import is allowed, FALSE otherwise.
   */
  public function isAllowed($allow_often = FALSE);

  /**
   * Import sessions from 3rd party service.
   */
  public function importSessions();

}
