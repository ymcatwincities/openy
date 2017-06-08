<?php

namespace Drupal\geolocation\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\Entity\EntityFormDisplay;

/**
 * Tests the creation of geolocation fields.
 *
 * @group geolocation
 */
class GeolocationFieldTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array(
    'field',
    'node',
    'geolocation',
  );

  protected $field;
  protected $webUser;
  protected $articleCreator;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->drupalCreateContentType(array('type' => 'article'));
    $this->articleCreator = $this->drupalCreateUser(array('create article content', 'edit own article content'));
    $this->drupalLogin($this->articleCreator);
  }

  /**
   * Helper function for testGeolocationField().
   */
  public function testGeolocationFieldLatlngWidget() {

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

    // Display creation form.
    $this->drupalGet('node/add/article');
    $this->assertFieldByName("field_geolocation[0][lat]", '', 'Geolocation lat input field found.');
    $this->assertFieldByName("field_geolocation[0][lng]", '', 'Geolocation lng input field found.');

    // Test basic entery of geolocation field.
    $lat = '49.880657';
    $lat_sexagesimal = '49° 52\' 50.3652"';
    $lng = '10.869212';
    $lng_sexagesimal = '10° 52\' 9.1632"';
    $edit = array(
      'title[0][value]' => $this->randomMachineName(),
      'field_geolocation[0][lat]' => $lat,
      'field_geolocation[0][lng]' => $lng,
    );

    // Test if the raw lat, lng values are found on the page.
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertRaw($lat, 'Latitude value found on the article node page.');
    $this->assertRaw($lng, 'Longitude value found on the article node page.');

    $this->drupalGet('node/1/edit');

    $this->assertRaw(htmlspecialchars($lat_sexagesimal, ENT_QUOTES), 'Latitude sexagesimal value found on node edit page.');
    $this->assertRaw(htmlspecialchars($lng_sexagesimal, ENT_QUOTES), 'Longitude sexagesimal value found on node edit page.');

    $edit = array(
      'field_geolocation[0][lat]' => $lat_sexagesimal,
      'field_geolocation[0][lng]' => $lng_sexagesimal,
    );

    // Test if the raw lat, lng values are found on the page.
    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertRaw($lat, 'Latitude value correct for sexagesimal input.');
    $this->assertRaw($lng, 'Longitude value correct for sexagesimal input.');
  }

  /**
   * Helper function for testGeolocationField().
   */
  public function testGeolocationFieldGeocoderWidgetEmptyRequired() {

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
      'required' => TRUE,
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

    // Display creation form.
    $this->drupalGet('node/add/article');
    $this->assertFieldByName("field_geolocation[0][lat]", '', 'Geolocation lat input field found.');
    $this->assertFieldByName("field_geolocation[0][lng]", '', 'Geolocation lng input field found.');

    $edit = array(
      'title[0][value]' => $this->randomMachineName(),
    );

    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText('No location has been selected yet for required field Geolocation', 'Empty message found for required field.');
  }

  /**
   * Helper function for testGeolocationField().
   */
  public function testGeolocationFieldHtml5WidgetEmptyRequired() {

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
      'required' => TRUE,
    ])->save();

    EntityFormDisplay::load('node.article.default')
      ->setComponent('field_geolocation', [
        'type' => 'geolocation_html5',
      ])
      ->save();

    EntityViewDisplay::load('node.article.default')
      ->setComponent('field_geolocation', [
        'type' => 'geolocation_latlng',
        'weight' => 1,
      ])
      ->save();

    // Display creation form.
    $this->drupalGet('node/add/article');
    $this->assertFieldByName("field_geolocation[0][lat]", '', 'Geolocation lat input field found.');
    $this->assertFieldByName("field_geolocation[0][lng]", '', 'Geolocation lng input field found.');

    $edit = array(
      'title[0][value]' => $this->randomMachineName(),
    );

    $this->drupalPostForm(NULL, $edit, t('Save'));
    $this->assertText('No location could be determined for required field Geolocation.', 'Empty message found for required field.');
  }

}
