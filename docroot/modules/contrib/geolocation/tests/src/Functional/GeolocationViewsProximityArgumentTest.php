<?php

namespace Drupal\Tests\geolocation\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the grid style plugin.
 *
 * @group geolocation
 */
class GeolocationViewsProximityArgumentTest extends BrowserTestBase {

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
  protected $viewsPath = 'geolocation-demo/proximity_argument_and_sort';

  /**
   * Tests the CommonMap style.
   */
  public function testProximityNoLocations() {
    $this->drupalGet($this->viewsPath);
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests the CommonMap style.
   */
  public function testNoProximityLocations() {
    $entity_test_storage = \Drupal::entityTypeManager()->getStorage('node');

    $entity_test_storage->create([
      'id' => 1,
      'title' => 'Proximity 1',
      'body' => 'test test',
      'type' => 'geolocation_default_article',
      'field_geolocation_demo_single' => [
        'lat' => 52,
        'lng' => 47,
      ],
    ])->save();
    $entity_test_storage->create([
      'id' => 2,
      'title' => 'Proximity 2',
      'body' => 'bar test',
      'type' => 'geolocation_default_article',
      'field_geolocation_demo_single' => [
        'lat' => 53,
        'lng' => 48,
      ],
    ])->save();
    $entity_test_storage->create([
      'id' => 3,
      'title' => 'Proximity 3',
      'body' => 'test foobar',
      'type' => 'geolocation_default_article',
      'field_geolocation_demo_single' => [
        'lat' => 54,
        'lng' => 49,
      ],
    ])->save();

    $this->drupalGet($this->viewsPath);
    $this->assertSession()->statusCodeEquals(200);

    $this->assertSession()->responseNotContains('Proximity 1');
    $this->assertSession()->responseNotContains('Proximity 2');
    $this->assertSession()->responseNotContains('Proximity 3');

    $this->drupalGet($this->viewsPath . '/52,47<=1miles');
    $this->assertSession()->statusCodeEquals(200);

    $this->assertSession()->responseContains('Proximity 1');
    $this->assertSession()->responseNotContains('Proximity 2');
    $this->assertSession()->responseNotContains('Proximity 3');

    $this->drupalGet($this->viewsPath . '/52,47<=140');
    $this->assertSession()->statusCodeEquals(200);

    $this->assertSession()->responseContains('Proximity 1');
    $this->assertSession()->responseContains('Proximity 2');
    $this->assertSession()->responseNotContains('Proximity 3');

    $this->drupalGet($this->viewsPath . '/52,47>140');
    $this->assertSession()->statusCodeEquals(200);

    $this->assertSession()->responseNotContains('Proximity 1');
    $this->assertSession()->responseNotContains('Proximity 2');
    $this->assertSession()->responseContains('Proximity 3');
  }

  /**
   * Tests to ensure rounding error doesn't occur (d.o #2796999).
   */
  public function testRoundingError() {
    $entity_test_storage = \Drupal::entityTypeManager()->getStorage('node');

    $entity_test_storage->create([
      'title' => 'Proximity 4',
      'body' => 'test test',
      'type' => 'geolocation_default_article',
      'field_geolocation_demo_single' => [
        'lat' => 51.4545,
        'lng' => -2.5879,
      ],
    ])->save();

    $this->drupalGet($this->viewsPath . '/51.4545,-2.5879<5miles');
    $this->assertSession()->statusCodeEquals(200);

    $this->assertSession()->responseContains('Proximity 4');
  }

  /**
   * Tests to ensure views argument is parsed correctly (d.o #2856948)
   */
  public function testArgumentParse() {
    $entity_test_storage = \Drupal::entityTypeManager()->getStorage('node');

    $entity_test_storage->create([
      'title' => 'Proximity 5',
      'body' => 'test test',
      'type' => 'geolocation_default_article',
      'field_geolocation_demo_single' => [
        'lat' => 51.4545,
        'lng' => -2.5879,
      ],
    ])->save();

    $this->drupalGet($this->viewsPath . '/52.5,-0.5<=5000');
    $this->assertSession()->statusCodeEquals(200);

    $this->assertSession()->responseContains('Proximity 5');
  }

}
