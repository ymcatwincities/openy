<?php

/**
 * @file
 * Contains \Drupal\purge\Tests\Invalidation\RegularExpressionInvalidationTest.
 */

namespace Drupal\purge\Tests\Invalidation;

use Drupal\purge\Tests\Invalidation\PluginTestBase;

/**
 * Tests \Drupal\purge\Plugin\Purge\Invalidation\RegularExpressionInvalidation.
 *
 * @group purge
 * @see \Drupal\purge\Plugin\Purge\Invalidation\InvalidationInterface
 */
class RegularExpressionInvalidationTest extends PluginTestBase {
  protected $plugin_id = 'regex';
  protected $expressions = ['\.(jpg|jpeg|css|js)$'];
  protected $expressionsInvalid = [NULL, ''];

}
