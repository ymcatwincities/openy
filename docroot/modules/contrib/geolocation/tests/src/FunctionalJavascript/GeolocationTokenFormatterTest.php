<?php

namespace Drupal\Tests\geolocation\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\JavascriptTestBase;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\Entity\EntityFormDisplay;

/**
 * Tests the Google Geocoder Token Formatter functionality.
 *
 * @group geolocation
 */
class GeolocationTokenFormatterTest extends JavascriptTestBase {

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
      ])
      ->save();

    EntityViewDisplay::load('node.article.default')
      ->setComponent('field_geolocation', [
        'type' => 'geolocation_latlng',
        'weight' => 1,
      ])
      ->save();

    $entity_test_storage = \Drupal::entityTypeManager()->getStorage('node');
    $entity_test_storage->create([
      'id' => 1,
      'title' => 'Test node 1',
      'body' => 'test test',
      'type' => 'article',
      'field_geolocation' => [
        'lat' => 52,
        'lng' => 47,
      ],
    ])->save();
  }

  /**
   * Tests the token formatter.
   */
  public function testGeocoderTokenizedTestReplacement() {
    $this->drupalGet('node/1');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains('<span class="geolocation-latlng">52, 47</span>');

    EntityViewDisplay::load('node.article.default')
      ->setComponent('field_geolocation', [
        'type' => 'geolocation_token',
        'settings' => [
          'tokenized_text' => '<div class="testing">[geolocation_current_item:lat]/[geolocation_current_item:lng]</div>',
        ],
        'weight' => 1,
      ])
      ->save();

    $this->drupalGet('node/1');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->responseContains('<div class="testing">52/47</div>');
  }

}
