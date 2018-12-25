<?php

namespace Drupal\purge\Tests\Invalidation;

use Drupal\purge\Tests\Invalidation\PluginTestBase;

/**
 * Tests \Drupal\purge\Plugin\Purge\Invalidation\PathInvalidation.
 *
 * @group purge
 * @see \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface
 */
class PathInvalidationTest extends PluginTestBase {
  protected $plugin_id = 'path';
  protected $expressions = [
    '',
    '?page=0',
    'news',
    'news/',
    '012/442',
    'news/article-1',
    'news/article-1?page=0&secondparam=1',
  ];
  protected $expressionsInvalid = [
    NULL,
    '/news',
    'news/*',
    '/news/*',
    '*',
  ];

}
