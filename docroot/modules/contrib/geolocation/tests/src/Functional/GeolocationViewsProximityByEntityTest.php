<?php

namespace Drupal\Tests\geolocation\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the proximity views sort.
 *
 * @group geolocation
 */
class GeolocationViewsProximityByEntityTest extends BrowserTestBase {

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
   * Tests the proximity sort.
   */
  public function testEmpty() {
    $this->drupalGet('geolocation-demo/proximity-by-entity-id/');
    $this->assertSession()->statusCodeEquals(200);

    $this->assertSession()->responseNotContains('Closest Node');
    $this->assertSession()->responseNotContains('130.18');
  }

  /**
   * Tests the proximity sort.
   */
  public function testProximityByEntity() {
    /** @var \Drupal\node\NodeStorageInterface $entity_test_storage */
    $entity_test_storage = \Drupal::entityTypeManager()->getStorage('node');

    $origin_node = $entity_test_storage->create([
      'id' => 1,
      'title' => 'Proximity Origin Node',
      'body' => 'test test',
      'type' => 'geolocation_default_article',
      'field_geolocation_demo_single' => [
        'lat' => 52,
        'lng' => 47,
      ],
    ]);
    $origin_node->save();

    $entity_test_storage->create([
      'title' => 'Closest Node',
      'body' => 'bar test',
      'type' => 'geolocation_default_article',
      'field_geolocation_demo_single' => [
        'lat' => 53,
        'lng' => 48,
      ],
    ])->save();

    $this->drupalGet('geolocation-demo/proximity-by-entity-id/' . $origin_node->id());
    $this->assertSession()->statusCodeEquals(200);

    $this->assertSession()->responseNotContains('Proximity Origin Node');

    $this->assertSession()->responseContains('Closest Node');
    $this->assertSession()->responseContains('130.18');
  }

}
