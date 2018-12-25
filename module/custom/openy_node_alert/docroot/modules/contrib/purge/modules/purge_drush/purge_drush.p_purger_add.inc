<?php

/**
 * @file
 * Administrative Drush commands for Purge - Command File.
 */

use Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface;

/**
 * Create a new purger instance.
 *
 * @param string $format
 *   The default or selected output format of the command.
 * @param \Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface $purgePurgers
 *   The purge purgers service.
 * @param string $plugin_id
 *   The plugin ID of the purger instance to create.
 * @param bool $if_not_exists
 *   Don't create a new purger if one of this type exists.
 *
 * @return array
 *   Drush render/format array.
 */
function purge_drush_p_purger_add($format, PurgersServiceInterface $purgePurgers, $plugin_id, $if_not_exists) {
  $enabled = $purgePurgers->getPluginsEnabled();
  $available = $purgePurgers->getPluginsAvailable();

  // Verify that the plugin exists.
  if (!isset($purgePurgers->getPlugins()[$plugin_id])) {
    if (in_array($format, ['yaml', 'json'])) {
      return FALSE;
    }
    return drush_set_error('ERROR', dt('The given plugin does not exist!'));
  }

  // When --if-not-exists is passed, we cancel creating double purgers.
  if ($if_not_exists) {
    if (in_array($plugin_id, $enabled)) {
      if (in_array($format, ['yaml', 'json'])) {
        return TRUE;
      }
      return dt('The purger already exists!');
    }
  }

  // Verify that new instances of the plugin may be created.
  if (!in_array($plugin_id, $purgePurgers->getPluginsAvailable())) {
    if (in_array($format, ['yaml', 'json'])) {
      return FALSE;
    }
    return drush_set_error('ERROR', dt('No more instances of this plugin can be created!'));
  }

  // Define the new instance and store it.
  $enabled[$purgePurgers->createId()] = $plugin_id;
  $purgePurgers->setPluginsEnabled($enabled);
  if (in_array($format, ['yaml', 'json'])) {
    return TRUE;
  }
  return dt('The purger has been created!');
}
