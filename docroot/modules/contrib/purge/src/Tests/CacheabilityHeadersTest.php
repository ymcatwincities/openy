<?php

/**
 * @file
 * Contains \Drupal\purge\Tests\CacheabilityHeadersTest.
 */

namespace Drupal\purge\Tests;

use Drupal\purge\Tests\KernelTestBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tests purge.services.yml enabling the X-Drupal-Cache-Tags response header.
 *
 * @group purge
 */
class CacheabilityHeadersTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system'];

  /**
   * {@inheritdoc}
   */
  function setUp() {
    parent::setUp();
    $this->installSchema('system', ['router']);
    \Drupal::service('router.builder')->rebuild();
  }

  /**
   * Test header presence on a frontpage request.
   */
  public function testHeaderPresence() {
    $request = Request::create('/');
    $response = \Drupal::getContainer()->get('http_kernel')->handle($request);
    $this->assertFalse(is_null($response->headers->get('X-Drupal-Cache-Tags')));
    $this->assertTrue(is_string($response->headers->get('X-Drupal-Cache-Tags')));
    $this->assertTrue(strlen($response->headers->get('X-Drupal-Cache-Tags')));
  }

}
