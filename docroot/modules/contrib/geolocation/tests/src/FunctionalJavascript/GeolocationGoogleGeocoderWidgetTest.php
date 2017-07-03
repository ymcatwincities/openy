<?php

namespace Drupal\Tests\geolocation\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\JavascriptTestBase;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\Entity\EntityFormDisplay;

/**
 * Tests the Google Geocoder Widget functionality.
 *
 * @group geolocation
 */
class GeolocationGoogleGeocoderWidgetTest extends JavascriptTestBase {

  use GeolocationGoogleTestTrait;

  public $adminUser;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'field',
    'geolocation',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser([
      'bypass node access',
      'administer nodes',
    ]);

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
        'type' => 'geolocation_googlegeocoder',
        'settings' => [
          'allow_override_map_settings' => TRUE,
        ],
      ])
      ->save();

    EntityViewDisplay::load('node.article.default')
      ->setComponent('field_geolocation', [
        'type' => 'geolocation_map',
        'settings' => [
          'use_overridden_map_settings' => TRUE,
        ],
        'weight' => 1,
      ])
      ->save();

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
    $entity_test_storage->create([
      'id' => 4,
      'title' => 'Custom map settings',
      'body' => 'This content tests if the custom map settings are respected',
      'type' => 'article',
      'field_geolocation' => [
        'lat' => 54,
        'lng' => 49,
        'data' => [
          'google_map_settings' => [
            'height' => '376px',
            'width' => '229px',
          ],
        ],
      ],
    ])->save();
  }

  /**
   * Tests the GoogleMap widget.
   */
  public function testGeocoderWidgetMapPresent() {
    $this->drupalLogin($this->adminUser);

    $this->drupalGetFilterGoogleKey('node/3/edit');
    $this->assertSession()->statusCodeEquals(200);

    $this->assertSession()->elementExists('css', '.geolocation-map-canvas');

    // If Google works, either gm-style or gm-err-container will be present.
    $this->assertSession()->elementExists('css', '.geolocation-map-canvas [class^="gm-"]');
  }

  /**
   * Tests the GoogleMap widget.
   */
  public function testGeocoderWidgetEmptyValuePreserved() {
    EntityFormDisplay::load('node.article.default')
      ->setComponent('field_geolocation', [
        'type' => 'geolocation_googlegeocoder',
        'settings' => [
          'default_latitude' => 12,
          'default_longitude' => 24,
        ],
      ])
      ->save();

    $this->drupalLogin($this->adminUser);

    $this->drupalGetFilterGoogleKey('node/add/article');
    $this->assertSession()->statusCodeEquals(200);

    $page = $this->getSession()->getPage();
    $page->findField('Title')->setValue('I am new');
    $page->pressButton('Save and publish');

    /** @var \Drupal\node\NodeInterface $new_node */
    $new_node = \Drupal::entityTypeManager()->getStorage('node')->load(5);
    $this->assertSession()->assert($new_node->get('field_geolocation')->isEmpty(), "Node geolocation field empty after saving from predefined location widget");
  }

}
