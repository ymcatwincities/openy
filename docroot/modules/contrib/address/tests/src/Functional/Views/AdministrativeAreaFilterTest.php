<?php

namespace Drupal\Tests\address\Functional\Views;

use Drupal\Tests\BrowserTestBase;
use Drupal\views\Views;

/**
 * Tests the administrative area Views filter for Address fields.
 *
 * @group address
 */
class AdministrativeAreaFilterTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'user',
    'views',
    'address',
    'address_test',
  ];

  /**
   * A simple user with 'access content' permission.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->user = $this->drupalCreateUser(['access content']);
    $this->drupalLogin($this->user);
  }

  /**
   * Test options for administrative area using a static country code.
   */
  public function testStaticCountryAdministrativeAreaOptions() {
    $view = Views::getView('address_test_filter_administrative_area');
    $filters = $view->getDisplay()->getOption('filters');
    $filters['field_address_test_administrative_area']['country']['country_source'] = 'static';
    $filters['field_address_test_administrative_area']['country']['country_static_code'] = 'BR';
    $view->getDisplay()->overrideOption('filters', $filters);
    $view->save();

    $this->drupalGet('address-test/views/filter-administrative-area');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldExists('field_address_test_administrative_area');
    $this->assertAdministrativeAreaOptions('BR');
  }

  /**
   * Test options for administrative area using a contextual country filter.
   */
  public function testContextualCountryFilterAdministrativeAreaOptions() {
    $view = Views::getView('address_test_filter_administrative_area');
    $filters = $view->getDisplay()->getOption('filters');
    $filters['field_address_test_administrative_area']['country']['country_source'] = 'argument';
    $filters['field_address_test_administrative_area']['country']['country_argument_id'] = 'field_address_test_country_code';
    $view->getDisplay()->overrideOption('filters', $filters);
    $view->save();

    // With no country selected, the administrative area shouldn't exist.
    $this->drupalGet('address-test/views/filter-administrative-area');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldNotExists('field_address_test_administrative_area');

    // For a country without admin areas, the filter still shouldn't exist.
    $this->drupalGet('address-test/views/filter-administrative-area/CR');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldNotExists('field_address_test_administrative_area');

    // For countries with administrative areas, validate the options.
    foreach (['BR', 'EG', 'MX', 'US'] as $country) {
      $this->drupalGet("address-test/views/filter-administrative-area/$country");
      $this->assertSession()->statusCodeEquals(200);
      $this->assertSession()->fieldExists('field_address_test_administrative_area');
      $this->assertAdministrativeAreaOptions($country);
    }
  }

  /**
   * Test options for administrative area using an exposed country filter.
   */
  public function testExposedCountryFilterAdministrativeAreaOptions() {
    $view = Views::getView('address_test_filter_administrative_area');
    $filters = $view->getDisplay()->getOption('filters');
    $filters['field_address_test_administrative_area']['country']['country_source'] = 'filter';
    $filters['field_address_test_administrative_area']['country']['country_filter_id'] = 'field_address_test_country_code';
    $view->getDisplay()->overrideOption('filters', $filters);
    $view->save();

    // With no country selected, the administrative area shouldn't exist.
    $this->drupalGet('address-test/views/filter-administrative-area');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldNotExists('field_address_test_administrative_area');

    // For a country without admin areas, the filter still shouldn't exist.
    $options = ['query' => ['field_address_test_country_code' => 'CR']];
    $this->drupalGet('address-test/views/filter-administrative-area', $options);
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldNotExists('field_address_test_administrative_area');

    // For countries with admin areas, validate the options.
    foreach (['BR', 'EG', 'MX', 'US'] as $country) {
      $options = ['query' => ['field_address_test_country_code' => $country]];
      $this->drupalGet('address-test/views/filter-administrative-area', $options);
      $this->assertSession()->statusCodeEquals(200);
      $this->assertSession()->fieldExists('field_address_test_administrative_area');
      $this->assertAdministrativeAreaOptions($country);
    }
  }

  /**
   * Test that the static vs. dynamic label feature works properly.
   */
  public function testAdministrativeAreaLabels() {
    $static_label = 'Administrative area (static label)';
    $dynamic_labels = [
      'AE' => 'Emirate',
      'BR' => 'State',
      'CA' => 'Province',
      'US' => 'State',
    ];

    // Force the view into our expected configuration. Use contextual filter
    // to set the country, and start with static labels.
    $view = Views::getView('address_test_filter_administrative_area');
    $filters = $view->getDisplay()->getOption('filters');
    $filters['field_address_test_administrative_area']['country']['country_source'] = 'argument';
    $filters['field_address_test_administrative_area']['country']['country_argument_id'] = 'field_address_test_country_code';
    $filters['field_address_test_administrative_area']['expose']['label_type'] = 'static';
    $filters['field_address_test_administrative_area']['expose']['label'] = $static_label;
    $view->getDisplay()->overrideOption('filters', $filters);
    $view->save();

    foreach ($dynamic_labels as $country => $dynamic_label) {
      $this->drupalGet("address-test/views/filter-administrative-area/$country");
      $this->assertSession()->pageTextContains($static_label);
      $this->assertSession()->pageTextNotContains($dynamic_label);
    }

    // Configure for dynamic labels and test again.
    $view = Views::getView('address_test_filter_administrative_area');
    $filters['field_address_test_administrative_area']['expose']['label_type'] = 'dynamic';
    $view->getDisplay()->overrideOption('filters', $filters);
    $view->save();

    foreach ($dynamic_labels as $country => $dynamic_label) {
      $this->drupalGet("address-test/views/filter-administrative-area/$country");
      $this->assertSession()->pageTextNotContains($static_label);
      $this->assertSession()->pageTextContains($dynamic_label);
    }
  }

  /**
   * Assert the right administrative area options for a given country code.
   *
   * @param string $active_country
   *   The country code.
   */
  protected function assertAdministrativeAreaOptions($active_country) {
    // These are not exhaustive lists, nor are the keys guaranteed to be unique.
    $areas = [
      'BR' => [
        'AM' => 'Amazonas',
        'BA' => 'Bahia',
        'PE' => 'Pernambuco',
        'RJ' => 'Rio de Janeiro',
      ],
      'EG' => [
        'Alexandria Governorate' => 'Alexandria Governorate',
        'Cairo Governorate' => 'Cairo Governorate',
      ],
      'MX' => [
        'CHIS' => 'Chiapas',
        'JAL' => 'Jalisco',
        'OAX' => 'Oaxaca',
        'VER' => 'Veracruz',
      ],
      'US' => [
        'LA' => 'Louisiana',
        'MA' => 'Massachusetts',
        'WI' => 'Wisconsin',
      ],
    ];
    foreach ($areas as $country => $areas) {
      foreach ($areas as $area_key => $area_value) {
        // For the active country, ensure both the key and value match.
        if ($country == $active_country) {
          $this->assertSession()->optionExists('edit-field-address-test-administrative-area', $area_key);
          $this->assertSession()->optionExists('edit-field-address-test-administrative-area', $area_value);
        }
        // Otherwise, we can't assume the keys are unique (e.g. 'MA' is a
        // state code in many different countries), so all we can safely
        // assume is that the state value strings aren't on the page.
        else {
          $this->assertSession()->pageTextNotContains($area_value);
        }
      }
    }
  }

}
