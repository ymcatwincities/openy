<?php

namespace Drupal\openy_digital_signage_groupex_schedule;

/**
 * Provides an interface for cron methods.
 *
 * @ingroup openy_digital_signage_groupex_schedule
 */
interface OpenYSessionsGroupExCronInterface {

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
   * Import sessions from GroupEx Pro.
   */
  public function importSessions();

}
