<?php

/**
 * Implements hook_drush_command().
 */
function flood_unblock_drush_command() {
  $items['flood_unblock'] = array(
    'callback'    => 'flood_unblock_clear_drush',
    'description' => dt('Empty flood for a certain event.'),
    'arguments'   => array(
      'path' => dt('The particular flood to clear. Omit this argument to choose from available floods.'),
    ),
    'aliases'     => array('flun'),
  );

  return $items;
}

/**
 * Implements hook_drush_help().
 */
function flood_unblock_drush_help($section) {
  switch ($section) {
    case 'drush:flood-unblock':
      return dt('Empty the flood table for the selected event.');
  }

  return FALSE;
}

/**
 * Command callback for drush flood-unblock.
 */
function flood_unblock_clear_drush($type = NULL) {
  $types = array(
    'all'  => 'flood_unblock_drush_clear',
    'ip'   => 'flood_unblock_drush_clear',
    'user' => 'flood_unblock_drush_clear',
  );

  if (!isset($type)) {
    $type = drush_choice($types, 'Enter a number to choose which flood to clear.', '!key');
    if (empty($type)) {
      return drush_user_abort();
    }
  }
  drush_op($types[$type], $type);

  return FALSE;
}

/**
 * Clear the flood events.
 */
function flood_unblock_drush_clear($type) {
  $flood_manager = \Drupal::service('flood_unblock.flood_unblock_manager');
  switch ($type) {
    case 'ip':
      $flood_manager->flood_unblock_clear_event('user.failed_login_ip', NULL);
      break;

    case 'user':
      $flood_manager->flood_unblock_clear_event('user.failed_login_user', NULL);
      break;

    default:
      $flood_manager->flood_unblock_clear_event('user.failed_login_ip', NULL);
      $flood_manager->flood_unblock_clear_event('user.failed_login_user', NULL);
      break;

  }

  if ($type != 'all') {
    drush_print(dt('Flood cleared for the !event event.', array('!event' => $type)));
  }
  else {
    drush_print(dt('Flood cleared for all events.'));
  }
}
