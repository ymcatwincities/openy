<?php

namespace Drupal\purge\Tests\TagsHeader;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\purge\Tests\KernelTestBase;

/**
 * Tests \Drupal\purge\EventSubscriber\CacheableResponseSubscriber.
 *
 * @group purge
 */
class CacheableResponseSubscriberTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['system', 'purge_tagsheader_test'];

  /**
   * Assert that a particular cache tags header is set.
   *
   * @param string $path
   *   The path of a route to test on.
   * @param string $header_name
   *   The name of the HTTP response header tested.
   */
  protected function assertCacheTagsHeader($path, $header_name) {
    $request = Request::create($path);
    $response = $this->container->get('http_kernel')->handle($request);
    $this->assertEqual(200, $response->getStatusCode());
    $header = $response->headers->get($header_name);
    $this->assertNotNull($header, "$header_name header exists.");
    $this->assertTrue(is_string($header));
    $this->assertTrue(strpos($header, 'config:user.role.anonymous') !== FALSE);
    $this->assertTrue(strpos($header, 'rendered') !== FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->installSchema('system', ['router']);
    \Drupal::service('router.builder')->rebuild();
  }

  /**
   * Test header presence.
   */
  public function testHeaderPresence() {
    $this->assertCacheTagsHeader('/system/401', 'Header-A');
    $this->assertCacheTagsHeader('/system/401', 'Header-B');
    $this->assertCacheTagsHeader('/system/401', 'Header-C');
  }

}
