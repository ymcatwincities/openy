<?php

namespace Drupal\Tests\address\Kernel;

use CommerceGuys\Addressing\Zone\Zone;
use Drupal\entity_test\Entity\EntityTest;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Tests the address_zone field.
 *
 * @group address
 */
class ZoneItemTest extends EntityKernelTestBase {

  /**
   * @var array
   */
  public static $modules = [
    'address',
  ];

  /**
   * The test entity.
   *
   * @var \Drupal\entity_test\Entity\EntityTest
   */
  protected $testEntity;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $field_storage = FieldStorageConfig::create([
      'field_name' => 'field_zone',
      'entity_type' => 'entity_test',
      'type' => 'address_zone',
      'cardinality' => 1,
    ]);
    $field_storage->save();

    $field = FieldConfig::create([
      'field_name' => 'field_zone',
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
    ]);
    $field->save();

    $entity = EntityTest::create([
      'name' => 'Test',
    ]);
    $entity->save();
    $this->testEntity = $entity;
  }

  /**
   * Tests storing and retrieving a zone from the field.
   */
  public function testZone() {
    $zone = new Zone([
      'id' => 'test',
      'label' => 'Test',
      'territories' => [
        ['country_code' => 'HU'],
        ['country_code' => 'RS'],
      ],
    ]);
    $this->testEntity->field_zone = $zone;
    $this->testEntity->save();

    $this->testEntity = $this->reloadEntity($this->testEntity);
    $this->assertEquals($zone, $this->testEntity->field_zone->value);
  }

}
