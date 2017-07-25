<?php

namespace Drupal\Tests\address\Kernel\Formatter;

use Drupal\entity_test\Entity\EntityTest;

/**
 * Tests the address_plain formatter.
 *
 * @group address
 */
class AddressPlainFormatterTest extends FormatterTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->createField('address', 'address_plain');
  }

  /**
   * Tests the rendered output.
   */
  public function testRender() {
    $entity = EntityTest::create([]);
    $entity->{$this->fieldName} = [
      'country_code' => 'AD',
      'locality' => 'Canillo',
      'postal_code' => 'AD500',
      'address_line1' => 'C. Prat de la Creu, 62-64',
    ];
    $this->renderEntityFields($entity, $this->display);

    // Confirm the expected elements, including the predefined locality
    // (properly escaped), country name.
    $expected_elements = [
      'C. Prat de la Creu, 62-64',
      'AD500',
      'Canillo',
      'Andorra',
    ];
    foreach ($expected_elements as $expected_element) {
      $this->assertRaw($expected_element);
    }

    // Confirm that an unrecognized locality is shown unmodified.
    $entity->{$this->fieldName}->locality = 'FAKE_LOCALITY';
    $this->renderEntityFields($entity, $this->display);
    $this->assertRaw('FAKE_LOCALITY');
  }

}
