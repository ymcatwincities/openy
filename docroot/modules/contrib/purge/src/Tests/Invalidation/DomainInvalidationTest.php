<?php

namespace Drupal\purge\Tests\Invalidation;

use Drupal\purge\Tests\Invalidation\PluginTestBase;

/**
 * Tests \Drupal\purge\Plugin\Purge\Invalidation\DomainInvalidation.
 *
 * @group purge
 * @see \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface
 */
class DomainInvalidationTest extends PluginTestBase {
  protected $plugin_id = 'domain';
  protected $expressions = ['sitea.com', 'www.site.com'];
  protected $expressionsInvalid = [NULL, ''];

}
