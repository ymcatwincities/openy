<?php

namespace Drupal\geolocation\Tests;

use Drupal\views\Tests\ViewTestBase;
use Drupal\views\Tests\ViewTestData;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\Entity\EntityFormDisplay;

/**
 * Tests the grid style plugin.
 *
 * @group views
 */
class GeolocationViewsStyleCommonMapTest extends ViewTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'field',
    'views',
    'geolocation',
    'geolocation_test_views',
  ];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['geolocation_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);

    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);

    // Add the geolocation field to the article content type.
    FieldStorageConfig::create([
      'field_name' => 'field_geolocation',
      'entity_type' => 'node',
      'type' => 'geolocation',
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_geolocation',
      'label' => 'Geolocation',
      'entity_type' => 'node',
      'bundle' => 'article',
    ])->save();

    EntityFormDisplay::load('node.article.default')
      ->setComponent('field_geolocation', [
        'type' => 'geolocation_latlng',
      ])
      ->save();

    EntityViewDisplay::load('node.article.default')
      ->setComponent('field_geolocation', [
        'type' => 'geolocation_latlng',
        'weight' => 1,
      ])
      ->save();

    $this->container->get('views.views_data')->clear();

    ViewTestData::createTestViews(get_class($this), ['geolocation_test_views']);
  }

  /**
   * Tests the CommonMap style.
   */
  public function testCommonMapNoLocations() {
    $this->drupalGet('geolocation-test');
    $this->assertResponse(200);
  }

  /**
   * Tests the CommonMap style.
   */
  public function testCommonMapLocationsEmpty() {
    $entity_test_storage = \Drupal::entityTypeManager()->getStorage('node');

    $entity_test_storage->create([
      'id' => 1,
      'title' => 'foo bar baz',
      'body' => 'test test',
      'type' => 'article',
    ])->save();

    $entity_test_storage->create([
      'id' => 2,
      'title' => 'foo test',
      'body' => 'bar test',
      'type' => 'article',
    ])->save();

    $entity_test_storage->create([
      'id' => 3,
      'title' => 'bar',
      'body' => 'test foobar',
      'type' => 'article',
    ])->save();

    $this->drupalGet('geolocation-test');
    $this->assertResponse(200);
  }

  /**
   * Tests the CommonMap style.
   */
  public function testCommonMapLocations() {
    $entity_test_storage = \Drupal::entityTypeManager()->getStorage('node');
    $entity_test_storage->create([
      'id' => 1,
      'title' => 'foo bar baz',
      'body' => 'test test',
      'type' => 'article',
      'field_geolocation' => [
        'lat' => 52,
        'lng' => 47,
      ],
    ])->save();
    $entity_test_storage->create([
      'id' => 2,
      'title' => 'foo test',
      'body' => 'bar test',
      'type' => 'article',
      'field_geolocation' => [
        'lat' => 53,
        'lng' => 48,
      ],
    ])->save();
    $entity_test_storage->create([
      'id' => 3,
      'title' => 'bar',
      'body' => 'test foobar',
      'type' => 'article',
      'field_geolocation' => [
        'lat' => 54,
        'lng' => 49,
      ],
    ])->save();

    $this->drupalGet('geolocation-test');
    $this->assertResponse(200);
  }

}
