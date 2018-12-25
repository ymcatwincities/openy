<?php

namespace Drupal\purge\Tests\Invalidation;

use Drupal\purge\Tests\Invalidation\PluginTestBase;

/**
 * Tests \Drupal\purge\Plugin\Purge\Invalidation\TagInvalidation.
 *
 * @group purge
 * @see \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface
 */
class TagInvalidationTest extends PluginTestBase {
  protected $plugin_id = 'tag';
  protected $expressions = [
    'tag',
    'user:1',
    'menu:footer',
  ];
  protected $expressionsInvalid = [
    NULL,
    '',
    ['node', '1'],
    'wildtag:*',
  ];

}
