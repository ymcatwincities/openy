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
    'locale',
    'language',
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

    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $node_storage->create([
      'id' => 1,
      'title' => 'foo bar baz',
      'body' => 'test test',
      'type' => 'article',
      'field_geolocation' => [
        'lat' => 52,
        'lng' => 47,
      ],
    ])->save();
    $node_storage->create([
      'id' => 2,
      'title' => 'foo test',
      'body' => 'bar test',
      'type' => 'article',
      'field_geolocation' => [
        'lat' => 53,
        'lng' => 48,
      ],
    ])->save();
    $node_storage->create([
      'id' => 3,
      'title' => 'bar',
      'body' => 'test foobar',
      'type' => 'article',
      'field_geolocation' => [
        'lat' => 54,
        'lng' => 49,
      ],
    ])->save();
    $node_storage->create([
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
   * Tests the Use Current Language option from the settings.
   *
   * Changes the language to French, checking for the French map.
   */
  public function testGoogleMapUsingCurrentLanguage() {
    // Log in as an administrator and change geolocation and language settings.
    $admin_user = $this->drupalCreateUser([
      'configure geolocation',
      'administer languages',
      'access administration pages',
    ]);
    $this->drupalLogin($admin_user);

    // Get the geolocation configuration settings page.
    $this->drupalGet('admin/config/services/geolocation');

    // Enable the checkbox to use current language.
    $edit = ['use_current_language' => 1];
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));

    // Add and set French as the language. See from LanguageSwitchingTest.
    $edit = ['predefined_langcode' => 'fr'];
    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add language'));

    \Drupal::service('language.config_factory_override')
      ->getOverride('fr', 'language.entity.fr')
      ->set('label', 'français')
      ->save();

    // Enable URL language detection and selection.
    $edit = ['language_interface[enabled][language-url]' => '1'];
    $this->drupalPostForm('admin/config/regional/language/detection', $edit, t('Save settings'));

    $this->drupalGet('fr/node/4');
    $this->assertSession()->elementExists('css', 'html[lang="fr"]');

    $anchor = $this->assertSession()->waitForElement('css', 'a[href^="https://maps.google.com"][href*="hl="]', 3000);
    // To control the test messages, search inside the anchor's href.
    // This is achieved by looking for the "hl" parameter in an anchor's href:
    // https://maps.google.com/maps?ll=54,49&z=10&t=m&hl=fr&gl=US&mapclient=apiv3
    $contains_french_link = strpos($anchor->getAttribute('href'), 'hl=fr');

    if ($contains_french_link === FALSE) {
      $this->fail('Did not find expected parameters from Google Maps link for French translation.');
    }
  }

  /**
   * Tests the CommonMap style.
   */
  public function testCommonMap() {
    $this->drupalGetFilterGoogleKey('geolocation-test');

    $this->assertSession()->elementExists('css', '.geolocation-common-map-container');
    $this->assertSession()->elementExists('css', '.geolocation-common-map-locations');

    // If Google works, either gm-style or gm-err-container will be present.
    $this->assertSession()->elementExists('css', '.geolocation-common-map-container [class^="gm-"]');
  }

  /**
   * Tests the Google Maps formatter.
   */
  public function testGoogleMapFormatter() {
    $this->drupalGetFilterGoogleKey('node/3');

    $this->assertSession()->elementExists('css', '.geolocation-google-map');

    // If Google works, either gm-style or gm-err-container will be present.
    $this->assertSession()->elementExists('css', '.geolocation-google-map [class^="gm-"]');
  }

  /**
   * Tests the Google Maps formatter.
   */
  public function testGoogleMapFormatterCustomSettings() {
    $this->drupalGetFilterGoogleKey('node/4');

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

    $edit = [
      'title[0][value]' => $this->randomMachineName(),
      'field_geolocation[0][google_map_settings][height]' => '273px',
    ];

    $this->drupalPostForm(NULL, $edit, t('Save'));

    $this->drupalGetFilterGoogleKey('node/4');

    $this->assertSession()->elementExists('css', '.geolocation-google-map');
    $this->assertSession()->elementAttributeContains('css', '.geolocation-google-map', 'style', 'height: 273px;');

    // If Google works, either gm-style or gm-err-container will be present.
    $this->assertSession()->elementExists('css', '.geolocation-google-map [class^="gm-"]');
  }

}
