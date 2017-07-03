<?php

namespace Drupal\Tests\geolocation\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\JavascriptTestBase;
use Drupal\views\Tests\ViewTestData;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\Entity\EntityFormDisplay;

/**
 * Tests the JavaScript functionality.
 *
 * @group geolocation
 */
class GeolocationJavascriptTest extends JavascriptTestBase {

  use GeolocationGoogleTestTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'field',
    'views',
    'views_test_config',
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
  protected function setUp() {
    parent::setUp();

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

    $this->container->get('views.views_data')->clear();

    ViewTestData::createTestViews(get_class($this), ['geolocation_test_views']);

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
   * Tests the CommonMap style.
   */
  public function testCommonMap() {
    $this->drupalGetFilterGoogleKey('geolocation-test');

    $this->assertSession()->statusCodeEquals(200);

    $this->assertSession()->elementExists('css', '.geolocation-common-map-container');
    $this->assertSession()->elementExists('css', '.geolocation-common-map-locations');

    // If Google works, either gm-style or gm-err-container will be present.
    $this->assertSession()->elementExists('css', '.geolocation-common-map-container [class^="gm-"]');
  }

  /**
   * Tests the GoogleMap formatter.
   */
  public function testGoogleMapFormatter() {
    $this->drupalGetFilterGoogleKey('node/3');
    $this->assertSession()->statusCodeEquals(200);

    $this->assertSession()->elementExists('css', '.geolocation-google-map');

    // If Google works, either gm-style or gm-err-container will be present.
    $this->assertSession()->elementExists('css', '.geolocation-google-map [class^="gm-"]');
  }

  /**
   * Tests the GoogleMap formatter.
   */
  public function testGoogleMapFormatterCustomSettings() {
    $this->drupalGetFilterGoogleKey('node/4');
    $this->assertSession()->statusCodeEquals(200);

    $this->assertSession()->elementExists('css', '.geolocation-google-map');
    $this->assertSession()->elementAttributeContains('css', '.geolocation-google-map', 'style', 'height: 376px');

    // If Google works, either gm-style or gm-err-container will be present.
    $this->assertSession()->elementExists('css', '.geolocation-google-map [class^="gm-"]');

    // TODO: Create node with custom settings and test it.
    $admin_user = $this->drupalCreateUser([
      'bypass node access',
      'administer nodes',
    ]);
    $this->drupalLogin($admin_user);
    // Display creation form.
    $this->drupalGetFilterGoogleKey('node/4/edit');

    $this->assertSession()->fieldExists("field_geolocation[0][google_map_settings][height]");

    $edit = array(
      'title[0][value]' => $this->randomMachineName(),
      'field_geolocation[0][google_map_settings][height]' => '273px',
    );

    $this->drupalPostForm(NULL, $edit, t('Save'));

    $this->drupalGetFilterGoogleKey('node/4');
    $this->assertSession()->statusCodeEquals(200);

    $this->assertSession()->elementExists('css', '.geolocation-google-map');
    $this->assertSession()->elementAttributeContains('css', '.geolocation-google-map', 'style', 'height: 273px;');

    // If Google works, either gm-style or gm-err-container will be present.
    $this->assertSession()->elementExists('css', '.geolocation-google-map [class^="gm-"]');
  }

}
