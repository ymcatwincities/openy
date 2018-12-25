<?php

/**
 * @file
 * Drush integration.
 */

/**
 * Implements hook_drush_command().
 */
function simple_sitemap_drush_command() {
  $items['simple_sitemap-generate'] = [
    'description' => 'Regenerate XML sitemaps for all languages according to the module settings.',
    'callback' => 'drush_simple_sitemap_generate',
    'drupal dependencies' => ['simple_sitemap'],
  ];
  return $items;
}

/**
 * Callback function for hook_drush_command().
 *
 * Regenerate sitemap for all languages.
 */
function drush_simple_sitemap_generate() {
  \Drupal::service('simple_sitemap.generator')->generateSitemap('drush');
}
