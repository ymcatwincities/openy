<?php

namespace Drupal\Tests\geolocation\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the grid style plugin.
 *
 * @group geolocation
 */
class GeolocationViewsCommonMapTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'field',
    'views',
    'taxonomy',
    'geolocation',
    'geolocation_demo',
  ];

  /**
   * Tests the boundary filter.
   */
  public function testStaticCommonMap() {
    $this->drupalGet('geolocation-demo/common-map');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests the boundary filter.
   */
  public function testAjaxCommonMap() {
    $this->drupalGet('geolocation-demo/common-map-ajax');
    $this->assertSession()->statusCodeEquals(200);
  }

}
