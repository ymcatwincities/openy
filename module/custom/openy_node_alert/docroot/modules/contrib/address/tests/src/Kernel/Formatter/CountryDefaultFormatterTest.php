<?php

namespace Drupal\Tests\address\Kernel\Formatter;

use Drupal\entity_test\Entity\EntityTest;

/**
 * Tests the address_country_default formatter.
 *
 * @group address
 */
class CountryDefaultFormatterTest extends FormatterTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->createField('address_country', 'address_country_default');
  }

  /**
   * Tests the rendered output.
   */
  public function testRender() {
    $entity = EntityTest::create([]);
    $entity->{$this->fieldName}->value = 'RS';
    $this->renderEntityFields($entity, $this->display);
    $this->assertRaw('Serbia');

    $entity->{$this->fieldName}->value = 'UNKNOWN';
    $this->renderEntityFields($entity, $this->display);
    $this->assertRaw('UNKNOWN');
  }

}
