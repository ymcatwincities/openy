<?php

namespace Drupal\Tests\address\Kernel\Formatter;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Tests the address_default formatter.
 *
 * @group address
 */
class AddressDefaultFormatterTest extends FormatterTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    ConfigurableLanguage::createFromLangcode('zh-hant')->save();

    $this->createField('address', 'address_default');
  }

  /**
   * Tests Andorra address formatting.
   */
  public function testAndorraAddress() {
    $entity = EntityTest::create([]);
    $entity->{$this->fieldName} = [
      'country_code' => 'AD',
      'locality' => 'Canillo',
      'postal_code' => 'AD500',
      'address_line1' => 'C. Prat de la Creu, 62-64',
    ];

    $this->renderEntityFields($entity, $this->display);
    // Andorra has no predefined administrative areas, but it does have
    // predefined localities, which must be shown.
    $expected = implode('', [
      'line1' => '<p class="address" translate="no">',
      'line2' => '<span class="address-line1">C. Prat de la Creu, 62-64</span><br>' . "\n",
      'line3' => '<span class="postal-code">AD500</span> <span class="locality">Canillo</span><br>' . "\n",
      'line4' => '<span class="country">Andorra</span>',
      'line5' => '</p>',
    ]);
    $this->assertRaw($expected, 'The AD address has been properly formatted.');
  }

  /**
   * Tests El Salvador address formatting.
   */
  public function testElSalvadorAddress() {
    $entity = EntityTest::create([]);
    $entity->{$this->fieldName} = [
      'country_code' => 'SV',
      'administrative_area' => 'Ahuachapán',
      'locality' => 'Ahuachapán',
      'address_line1' => 'Some Street 12',
    ];
    $this->renderEntityFields($entity, $this->display);
    $expected = implode('', [
      'line1' => '<p class="address" translate="no">',
      'line2' => '<span class="address-line1">Some Street 12</span><br>' . "\n",
      'line3' => '<span class="locality">Ahuachapán</span><br>' . "\n",
      'line4' => '<span class="administrative-area">Ahuachapán</span><br>' . "\n",
      'line5' => '<span class="country">El Salvador</span>',
      'line6' => '</p>',
    ]);
    $this->assertRaw($expected, 'The SV address has been properly formatted.');

    $entity->{$this->fieldName}->postal_code = 'CP 2101';
    $this->renderEntityFields($entity, $this->display);
    $expected = implode('', [
      'line1' => '<p class="address" translate="no">',
      'line2' => '<span class="address-line1">Some Street 12</span><br>' . "\n",
      'line3' => '<span class="postal-code">CP 2101</span>-<span class="locality">Ahuachapán</span><br>' . "\n",
      'line4' => '<span class="administrative-area">Ahuachapán</span><br>' . "\n",
      'line5' => '<span class="country">El Salvador</span>',
      'line6' => '</p>',
    ]);
    $this->assertRaw($expected, 'The SV address has been properly formatted.');
  }

  /**
   * Tests Taiwan address formatting.
   */
  public function testTaiwanAddress() {
    $language = \Drupal::languageManager()->getLanguage('zh-hant');
    \Drupal::languageManager()->setConfigOverrideLanguage($language);
    // Reload the country repository for the new language to take effect.
    $this->container->set('address.country_repository', NULL);

    $entity = EntityTest::create([]);
    $entity->{$this->fieldName} = [
      'langcode' => 'zh-hant',
      'country_code' => 'TW',
      'administrative_area' => 'Taipei City',
      'locality' => "Da'an District",
      'address_line1' => 'Sec. 3 Hsin-yi Rd.',
      'postal_code' => '106',
      // Any HTML in the fields is supposed to be escaped.
      'organization' => 'Giant <h2>Bike</h2> Store',
      'recipient' => 'Mr. Liu',
      'given_name' => 'Wu',
      'family_name' => 'Chen',
    ];
    $this->renderEntityFields($entity, $this->display);
    $expected = implode('', [
      'line1' => '<p class="address" translate="no">',
      'line2' => '<span class="country">台灣</span><br>' . "\n",
      'line3' => '<span class="postal-code">106</span><br>' . "\n",
      'line4' => '<span class="administrative-area">台北市</span><span class="locality">大安區</span><br>' . "\n",
      'line5' => '<span class="address-line1">Sec. 3 Hsin-yi Rd.</span><br>' . "\n",
      'line6' => '<span class="organization">Giant &lt;h2&gt;Bike&lt;/h2&gt; Store</span><br>' . "\n",
      'line7' => '<span class="family-name">Chen</span> <span class="given-name">Wu</span>',
      'line8' => '</p>',
    ]);
    $this->assertRaw($expected, 'The TW address has been properly formatted.');
  }

  /**
   * Tests US address formatting.
   */
  public function testUnitedStatesIncompleteAddress() {
    $entity = EntityTest::create([]);
    $entity->{$this->fieldName} = [
      'country_code' => 'US',
      'administrative_area' => 'CA',
      'address_line1' => '1098 Alta Ave',
      'postal_code' => '94043',
    ];
    $this->renderEntityFields($entity, $this->display);
    $expected = implode('', [
      'line1' => '<p class="address" translate="no">',
      'line2' => '<span class="address-line1">1098 Alta Ave</span><br>' . "\n",
      'line3' => '<span class="administrative-area">CA</span> <span class="postal-code">94043</span><br>' . "\n",
      'line4' => '<span class="country">United States</span>',
      'line5' => '</p>',
    ]);
    $this->assertRaw($expected, 'The US address has been properly formatted.');

    // Now add the locality, but remove the administrative area.
    $entity->{$this->fieldName}->locality = 'Mountain View';
    $entity->{$this->fieldName}->administrative_area = '';
    $this->renderEntityFields($entity, $this->display);
    $expected = implode('', [
      'line1' => '<p class="address" translate="no">',
      'line2' => '<span class="address-line1">1098 Alta Ave</span><br>' . "\n",
      'line3' => '<span class="locality">Mountain View</span>, <span class="postal-code">94043</span><br>' . "\n",
      'line4' => '<span class="country">United States</span>',
      'line5' => '</p>',
    ]);
    $this->assertRaw($expected, 'The US address has been properly formatted.');
  }

}
