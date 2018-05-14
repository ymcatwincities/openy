<?php

namespace Drupal\Tests\geolocation\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the grid style plugin.
 *
 * @group geolocation
 */
class GeolocationViewsBoundaryTest extends BrowserTestBase {

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
   * ID of the geolocation field in this test.
   *
   * @var string
   */
  protected $viewsPath = 'geolocation-demo/boundary-filter-fixed-values';

  /**
   * Tests the boundary filter.
   */
  public function testProximityNoLocations() {
    $this->drupalGet($this->viewsPath);
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests the boundary filter.
   *
   * It's currently locked to filter boundary of NE80,80 to SW20,20.
   */
  public function testBoundaryLocations() {
    $entity_test_storage = \Drupal::entityTypeManager()->getStorage('node');

    $entity_test_storage->create([
      'id' => 1,
      'title' => 'Boundary 1',
      'body' => 'test test',
      'type' => 'geolocation_default_article',
      'field_geolocation_demo_single' => [
        'lat' => 52,
        'lng' => 47,
      ],
    ])->save();
    $entity_test_storage->create([
      'id' => 2,
      'title' => 'Boundary 2',
      'body' => 'bar test',
      'type' => 'geolocation_default_article',
      'field_geolocation_demo_single' => [
        'lat' => 53,
        'lng' => 48,
      ],
    ])->save();
    $entity_test_storage->create([
      'id' => 3,
      'title' => 'Boundary 3',
      'body' => 'test foobar',
      'type' => 'geolocation_default_article',
      'field_geolocation_demo_single' => [
        'lat' => 5,
        'lng' => 5,
      ],
    ])->save();

    $this->drupalGet($this->viewsPath);
    $this->assertSession()->statusCodeEquals(200);

    $this->assertSession()->responseContains('Boundary 1');
    $this->assertSession()->responseContains('Boundary 2');
    $this->assertSession()->responseNotContains('Boundary 3');
  }

}
