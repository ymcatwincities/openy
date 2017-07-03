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
class GeolocationViewsProximityArgumentTest extends ViewTestBase {

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
  public static $testViews = ['geolocation_proximity_test'];

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
  protected $viewsPath = 'geolocation-proximity-test';

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
   * Tests the CommonMap style.
   */
  public function testProximityNoLocations() {
    $this->drupalGet($this->viewsPath);
    $this->assertResponse(200);
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
      'type' => 'article',
      $this->fieldId => [
        'lat' => 52,
        'lng' => 47,
      ],
    ])->save();
    $entity_test_storage->create([
      'id' => 2,
      'title' => 'Proximity 2',
      'body' => 'bar test',
      'type' => 'article',
      $this->fieldId => [
        'lat' => 53,
        'lng' => 48,
      ],
    ])->save();
    $entity_test_storage->create([
      'id' => 3,
      'title' => 'Proximity 3',
      'body' => 'test foobar',
      'type' => 'article',
      $this->fieldId => [
        'lat' => 54,
        'lng' => 49,
      ],
    ])->save();

    $this->drupalGet($this->viewsPath);
    $this->assertResponse(200);

    $this->assertText('Proximity 1', 'Proximity 1 element found.');
    $this->assertText('Proximity 2', 'Proximity 2 element found.');
    $this->assertText('Proximity 3', 'Proximity 3 element found.');

    $this->drupalGet($this->viewsPath . '/52,47<=1miles');
    $this->assertResponse(200);

    $this->assertText('Proximity 1', 'Proximity 1 element found.');
    $this->assertNoText('Proximity 2', 'Proximity 2 element not in proximity.');
    $this->assertNoText('Proximity 3', 'Proximity 3 element not in proximity.');

    $this->drupalGet($this->viewsPath . '/52,47<=140');
    $this->assertResponse(200);

    $this->assertText('Proximity 1', 'Proximity 1 element found.');
    $this->assertText('Proximity 2', 'Proximity 2 element found.');
    $this->assertNoText('Proximity 3', 'Proximity 3 element not in proximity.');

    $this->drupalGet($this->viewsPath . '/52,47>140');
    $this->assertResponse(200);

    $this->assertNoText('Proximity 1', 'Proximity 1 element not outside proximity.');
    $this->assertNoText('Proximity 2', 'Proximity 2 element not outside proximity.');
    $this->assertText('Proximity 3', 'Proximity 3 element found.');
  }

  /**
   * Tests to ensure rounding error doesn't occur (d.o #2796999).
   */
  public function testRoundingError() {
    $entity_test_storage = \Drupal::entityTypeManager()->getStorage('node');

    $entity_test_storage->create([
      'id' => 1,
      'title' => 'Proximity 1',
      'body' => 'test test',
      'type' => 'article',
      $this->fieldId => [
        'lat' => 51.4545,
        'lng' => -2.5879,
      ],
    ])->save();

    $this->drupalGet($this->viewsPath . '/51.4545,-2.5879<10000miles');
    $this->assertResponse(200);

    $this->assertText('Proximity 1', 'Proximity 1 element found.');
  }

}
