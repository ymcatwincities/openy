<?php

namespace Drupal\Tests\geolocation\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\Entity\EntityFormDisplay;

/**
 * Tests the creation of geolocation fields.
 *
 * @group geolocation
 */
class GeolocationFieldTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'field',
    'node',
    'taxonomy',
    'geolocation',
    'geolocation_demo',
  ];

  protected $field;
  protected $webUser;
  protected $articleCreator;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->articleCreator = $this->drupalCreateUser(['create geolocation_default_article content', 'edit own geolocation_default_article content']);
    $this->drupalLogin($this->articleCreator);
  }

  /**
   * Helper function for testGeolocationField().
   */
  public function testGeolocationFieldLatlngWidget() {
    EntityFormDisplay::load('node.geolocation_default_article.default')
      ->setComponent('field_geolocation_demo_single', [
        'type' => 'geolocation_latlng',
      ])
      ->save();

    EntityViewDisplay::load('node.geolocation_default_article.default')
      ->setComponent('field_geolocation_demo_single', [
        'type' => 'geolocation_latlng',
        'weight' => 1,
      ])
      ->save();

    // Display creation form.
    $this->drupalGet('node/add/geolocation_default_article');
    $this->assertSession()->fieldExists("field_geolocation_demo_single[0][lat]");
    $this->assertSession()->fieldExists("field_geolocation_demo_single[0][lng]");

    // Test basic entery of geolocation field.
    $lat = '49.880657';
    $lat_sexagesimal = '49° 52\' 50.3652"';
    $lng = '10.869212';
    $lng_sexagesimal = '10° 52\' 9.1632"';
    $edit = [
      'title[0][value]' => $this->randomMachineName(),
      'field_geolocation_demo_single[0][lat]' => $lat,
      'field_geolocation_demo_single[0][lng]' => $lng,
    ];

    // Test if the raw lat, lng values are found on the page.
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertSession()->responseContains($lat);
    $this->assertSession()->responseContains($lng);

    // TODO: Figure out the actually created NID instead of guessing.
    $this->drupalGet('node/101/edit');

    $this->assertSession()->responseContains(htmlspecialchars($lat_sexagesimal, ENT_QUOTES));
    $this->assertSession()->responseContains(htmlspecialchars($lng_sexagesimal, ENT_QUOTES));

    $edit = [
      'field_geolocation_demo_single[0][lat]' => $lat_sexagesimal,
      'field_geolocation_demo_single[0][lng]' => $lng_sexagesimal,
    ];

    // Test if the raw lat, lng values are found on the page.
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertSession()->responseContains($lat);
    $this->assertSession()->responseContains($lng);
  }

  /**
   * Helper function for testGeolocationField().
   */
  public function xxtestGeolocationFieldGeocoderWidgetEmptyRequired() {

    EntityFormDisplay::load('node.geolocation_default_article.default')
      ->setComponent('field_geolocation_demo_single', [
        'type' => 'geolocation_googlegeocoder',
      ])
      ->save();

    EntityViewDisplay::load('node.geolocation_default_article.default')
      ->setComponent('field_geolocation_demo_single', [
        'type' => 'geolocation_latlng',
        'weight' => 1,
      ])
      ->save();

    // Display creation form.
    $this->drupalGet('node/add/geolocation_default_article');
    $this->assertSession()->fieldExists("field_geolocation_demo_single[0][lat]");
    $this->assertSession()->fieldExists("field_geolocation_demo_single[0][lng]");

    $edit = [
      'title[0][value]' => $this->randomMachineName(),
    ];

    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertSession()->pageTextContains('No location has been selected yet for required field Geolocation');
  }

  /**
   * Helper function for testGeolocationField().
   */
  public function xxtestGeolocationFieldHtml5WidgetEmptyRequired() {

    EntityFormDisplay::load('node.geolocation_default_article.default')
      ->setComponent('field_geolocation_demo_single', [
        'type' => 'geolocation_html5',
      ])
      ->save();

    EntityViewDisplay::load('node.geolocation_default_article.default')
      ->setComponent('field_geolocation_demo_single', [
        'type' => 'geolocation_latlng',
        'weight' => 1,
      ])
      ->save();

    // Display creation form.
    $this->drupalGet('node/add/geolocation_default_article');
    $this->assertSession()->fieldExists("field_geolocation_demo_single[0][lat]");
    $this->assertSession()->fieldExists("field_geolocation_demo_single[0][lng]");

    $edit = [
      'title[0][value]' => $this->randomMachineName(),
    ];

    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertSession()->pageTextContains('No location could be determined for required field Geolocation.');
  }

}
