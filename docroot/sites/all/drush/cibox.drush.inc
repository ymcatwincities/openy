<?php

use Drupal\Core\DrupalKernel;
use Drupal\Core\Site\Settings;
use Symfony\Component\HttpFoundation\Request;

/**
 * Implementation of hook_drush_command().
 */
function cibox_drush_command() {
  $items = array();
  $items['cibox-cache-rebuild'] = array(
    'description' => 'Rebuild a Drupal 8 site and clear all its caches.',
    'options' => array(),
    'arguments' => array(),
    // Bootstrap to DRUSH_BOOTSTAP_DRUPAL_SITE to pick the correct site.
    // Further bootstrap is done by the rebuild script.
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_SITE,
    'core' => array('8+'),
    'aliases' => array('ciboxcr'),
  );
  return $items;
}

/**
 * Rebuild a Drupal 8 site in a quick way.
 */
function drush_cibox_cache_rebuild() {

  chdir(DRUPAL_ROOT);
// Clear the APC cache to ensure APC class loader is reset.
  if (function_exists('apc_fetch')) {
    apc_clear_cache('user');
  }
  $autoloader = drush_drupal_load_autoloader(DRUPAL_ROOT);
  require_once DRUSH_DRUPAL_CORE . '/includes/utility.inc';
  $request = Request::createFromGlobals();
// Manually resemble early bootstrap of DrupalKernel::boot().
  require_once DRUSH_DRUPAL_CORE . '/includes/bootstrap.inc';
  require_once DRUSH_DRUPAL_CORE . '/includes/database.inc';
  DrupalKernel::bootEnvironment();
// Avoid 'Only variables should be passed by reference'
  $root = DRUPAL_ROOT;
  $site_path = DrupalKernel::findSitePath($request);
  Settings::initialize($root, $site_path, $autoloader);
  $t = db_query('SHOW TABLES')->fetchAllKeyed(0, 0);
  $cached = array_filter(array_keys($t), function($v) {
    return substr($v, 0, 6) === 'cache_';
  });
  foreach (array_values($cached) as $tname) {
    db_query('TRUNCATE TABLE ' . $tname)->execute();
  }
// Use our error handler since _drupal_log_error() depends on an unavailable theme system (ugh).
  set_error_handler('drush_error_handler');
// drupal_rebuild() calls drupal_flush_all_caches() itself, so we don't do it manually.
  // drupal_rebuild($autoloader, $request);
  drush_log(dt('Fast Cache rebuild complete. Cleared %t tables', ['%t' => count($cached)]));

}