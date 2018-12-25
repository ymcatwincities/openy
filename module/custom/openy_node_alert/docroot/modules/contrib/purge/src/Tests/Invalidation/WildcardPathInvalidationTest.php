<?php

namespace Drupal\purge\Tests\Invalidation;

use Drupal\purge\Tests\Invalidation\PluginTestBase;

/**
 * Tests \Drupal\purge\Plugin\Purge\Invalidation\WildcardPathInvalidation.
 *
 * @group purge
 * @see \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface
 */
class WildcardPathInvalidationTest extends PluginTestBase {
  protected $plugin_id = 'wildcardpath';
  protected $expressions = [
    '*',
    '*?page=0',
    'news/*',
    'products/*',
  ];
  protected $expressionsInvalid = [
    NULL,
    '',
    '/*',
    '/',
    '?page=0',
    'news',
    'news/',
    '012/442',
    'news/article-1',
    'news/article-1?page=0&secondparam=1',
  ];

}
