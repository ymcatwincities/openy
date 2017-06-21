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
class GeolocationViewsBoundaryTest extends ViewTestBase {

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
  public static $testViews = ['geolocation_boundary_test'];

  /**
   * ID of the geolocation field in this test.
   *
   * @var string
   */
  protected $fieldId = 'field_geolocation';

  /**
   * ID of the geolocation field in this test.
   *
   * @var string
   */
  protected $viewsPath = 'geolocation-boundary-test';

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE) {
    parent::setUp($import_test_views);

    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);

    // Add the geolocation field to the article content type.
    FieldStorageConfig::create([
      'field_name' => $this->fieldId,
      'entity_type' => 'node',
      'type' => 'geolocation',
    ])->save();
    FieldConfig::create([
      'field_name' => $this->fieldId,
      'label' => 'Geolocation',
      'entity_type' => 'node',
      'bundle' => 'article',
    ])->save();

    EntityFormDisplay::load('node.article.default')
      ->setComponent($this->fieldId, [
        'type' => 'geolocation_latlng',
      ])
      ->save();

    EntityViewDisplay::load('node.article.default')
      ->setComponent($this->fieldId, [
        'type' => 'geolocation_latlng',
        'weight' => 1,
      ])
      ->save();

    $this->container->get('views.views_data')->clear();

    ViewTestData::createTestViews(get_class($this), ['geolocation_test_views']);
  }

  /**
   * Tests the boundary filter.
   */
  public function testProximityNoLocations() {
    $this->drupalGet($this->viewsPath);
    $this->assertResponse(200);
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
      'type' => 'article',
      $this->fieldId => [
        'lat' => 52,
        'lng' => 47,
      ],
    ])->save();
    $entity_test_storage->create([
      'id' => 2,
      'title' => 'Boundary 2',
      'body' => 'bar test',
      'type' => 'article',
      $this->fieldId => [
        'lat' => 53,
        'lng' => 48,
      ],
    ])->save();
    $entity_test_storage->create([
      'id' => 3,
      'title' => 'Boundary 3',
      'body' => 'test foobar',
      'type' => 'article',
      $this->fieldId => [
        'lat' => 5,
        'lng' => 5,
      ],
    ])->save();

    $this->drupalGet($this->viewsPath);
    $this->assertResponse(200);

    $this->assertText('Boundary 1', 'Boundary 1 element inside boundary.');
    $this->assertText('Boundary 2', 'Boundary 2 element inside boundary.');
    $this->assertNoText('Boundary 3', 'Boundary 3 element outside boundary.');
  }

}
