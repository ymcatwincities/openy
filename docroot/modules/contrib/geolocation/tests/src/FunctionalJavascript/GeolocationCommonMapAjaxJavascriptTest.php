<?php

namespace Drupal\Tests\geolocation\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\JavascriptTestBase;
use Drupal\views\Tests\ViewTestData;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\Entity\EntityFormDisplay;

/**
 * Tests the common map style AJAX JavaScript functionality.
 *
 * @group geolocation
 */
class GeolocationCommonMapAjaxJavascriptTest extends JavascriptTestBase {

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
  public static $testViews = ['geolocation_common_map_ajax_test'];

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

    $entity_test_storage = \Drupal::entityTypeManager()->getStorage('node');
    $entity_test_storage->create([
      'id' => 1,
      'title' => 'Location 1',
      'body' => 'location 1 test body',
      'type' => 'article',
      'field_geolocation' => [
        'lat' => 52,
        'lng' => 47,
      ],
    ])->save();
    $entity_test_storage->create([
      'id' => 2,
      'title' => 'Location 2',
      'body' => 'location 2 test body',
      'type' => 'article',
      'field_geolocation' => [
        'lat' => 53,
        'lng' => 48,
      ],
    ])->save();
    $entity_test_storage->create([
      'id' => 3,
      'title' => 'Location 3',
      'body' => 'location 3 test body',
      'type' => 'article',
      'field_geolocation' => [
        'lat' => 54,
        'lng' => 49,
      ],
    ])->save();
  }

  /**
   * Tests the CommonMap style.
   */
  public function testCommonMap() {
    $this->drupalGetFilterGoogleKey('geolocation-common-map-ajax-test');

    $this->assertSession()->statusCodeEquals(200);

    $this->assertSession()->elementExists('css', '.geolocation-common-map-container');
    $this->assertSession()->elementExists('css', '.geolocation-common-map-locations');

    // If Google works, either gm-style or gm-err-container will be present.
    $this->assertSession()->elementExists('css', '.geolocation-common-map-container [class^="gm-"]');
  }

  /**
   * Tests the CommonMap style.
   */
  public function testCommonMapAjax() {
    $this->drupalGetFilterGoogleKey('geolocation-common-map-ajax-test');

    $session = $this->getSession();

    $this->assertSession()->statusCodeEquals(200);

    $this->assertSession()->responseContains('Location 1');
    $this->assertSession()->responseContains('Location 3');

    $this->submitForm(
      [
        'field_geolocation_boundary[lat_north_east]' => '53',
        'field_geolocation_boundary[lng_north_east]' => '48',
        'field_geolocation_boundary[lat_south_west]' => '51',
        'field_geolocation_boundary[lng_south_west]' => '46',
      ],
      'Apply',
      'views-exposed-form-geolocation-common-map-ajax-test-page-1'
    );
    $this->assertSession()->assertWaitOnAjaxRequest();

    $html = $session->getPage()->getHtml();
    $this->assertContains('Location 1', $html);
    $this->assertNotContains('Location 3', $html);
  }

}
