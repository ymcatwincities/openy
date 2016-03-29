<?php
/**
 * @file
 * Drush integration.
 */

use Drupal\simple_sitemap\Simplesitemap;

/**
 * Implements hook_drush_command().
 */
function simple_sitemap_drush_command() {
  $items['simple_sitemap-generate'] = array(
    'description' => 'Regenerate XML sitemaps for all languages according to the module settings.',
    'callback' => 'drush_simple_sitemap_generate',
    'drupal dependencies' => array('simple_sitemap'),
  );
  return $items;
}

/**
 * Callback function for hook_drush_command().
 *
 * Regenerate sitemap for all languages.
 */
function drush_simple_sitemap_generate() {
  $sitemap = new Simplesitemap;
  $sitemap->generate_sitemap('drush');
}
