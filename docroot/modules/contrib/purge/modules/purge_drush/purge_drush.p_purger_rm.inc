<?php

/**
 * @file
 * Administrative Drush commands for Purge - Command File.
 */

use Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface;

/**
 * Remove a purger instance.
 *
 * @param string $format
 *   The default or selected output format of the command.
 * @param \Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface $purgePurgers
 *   The purge purgers service.
 * @param string $instance_id
 *   The instance ID of the purger to remove.
 *
 * @return array
 *   Drush render/format array.
 */
function purge_drush_p_purger_rm($format, PurgersServiceInterface $purgePurgers, $instance_id) {
  $enabled = $purgePurgers->getPluginsEnabled();

  // Verify that the purger instance exists.
  if (!isset($enabled[$instance_id])) {
    if (in_array($format, ['yaml', 'json'])) {
      return FALSE;
    }
    return drush_set_error('ERROR', dt('The given instance ID is not valid!'));
  }

  // Remove the purger instance and finish command execution.
  unset($enabled[$instance_id]);
  $purgePurgers->setPluginsEnabled($enabled);
  if (in_array($format, ['yaml', 'json'])) {
    return TRUE;
  }
  return dt('The purger has been removed!');
}
