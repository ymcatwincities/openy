<?php

namespace Drupal\purge\Tests\Invalidation;

use Drupal\purge\Tests\Invalidation\PluginTestBase;

/**
 * Tests \Drupal\purge\Plugin\Purge\Invalidation\EverythingInvalidation.
 *
 * @group purge
 * @see \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface
 */
class EverythingInvalidationTest extends PluginTestBase {
  protected $plugin_id = 'everything';
  protected $expressions = [NULL];
  protected $expressionsInvalid = ['', 'foobar'];

}
