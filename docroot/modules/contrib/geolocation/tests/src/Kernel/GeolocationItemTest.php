<?php

namespace Drupal\Tests\geolocation\Kernel;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Tests\field\Kernel\FieldKernelTestBase;

/**
 * Tests the new entity API for the geolocation field type.
 *
 * @group geolocation
 */
class GeolocationItemTest extends FieldKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['geolocation'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create a geolocation field storage and field for validation.
    FieldStorageConfig::create([
      'field_name' => 'field_test',
      'entity_type' => 'entity_test',
      'type' => 'geolocation',
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_test',
      'label' => 'Geolocation',
      'entity_type' => 'entity_test',
      'bundle' => 'entity_test',
    ])->save();
  }

  /**
   * Tests using entity fields of the geolocation field type.
   */
  public function testGeolocationItem() {
    $entityTestStorage = \Drupal::entityTypeManager()->getStorage('entity_test');
    $lat = '49.880657';
    $lng = '10.869212';
    $data = 'Foo bar';

    // Verify entity creation.
    $entity = $entityTestStorage->create([
      'title' => $this->randomMachineName(),
      'field_test' => [
        'lat' => $lat,
        'lng' => $lng,
        'data' => $data,
      ],
    ]);
    $entity->save();

    // Verify entity has been created properly.
    $id = $entity->id();
    /** @var \Drupal\entity_test\Entity\EntityTest $entity */
    $entity = $entityTestStorage->load($id);
    $this->assertTrue($entity->get('field_test') instanceof FieldItemListInterface, 'Field implements interface.');
    $this->assertTrue($entity->get('field_test')[0] instanceof FieldItemInterface, 'Field item implements interface.');
    $this->assertEquals($entity->get('field_test')->lat, $lat, "Lat {$entity->get('field_test')->lat} is equal to lat {$lat}.");
    $this->assertEquals($entity->get('field_test')[0]->lat, $lat, "Lat {$entity->get('field_test')[0]->lat} is equal to lat {$lat}.");
    $this->assertEquals($entity->get('field_test')->lng, $lng, "Lng {$entity->get('field_test')->lng} is equal to lng {$lng}.");
    $this->assertEquals($entity->get('field_test')[0]->lng, $lng, "Lng {$entity->get('field_test')[0]->lng} is equal to lng {$lng}.");

    $this->assertEquals(round($entity->get('field_test')->lat_sin, 5), round(sin(deg2rad($lat)), 5), "Sine for latitude calculated correctly.");
    $this->assertEquals(round($entity->get('field_test')->lat_cos, 5), round(cos(deg2rad($lat)), 5), "Cosine for latitude calculated correctly.");
    $this->assertEquals(round($entity->get('field_test')->lng_rad, 5), round(deg2rad($lng), 5), "Radian value for longitude calculated correctly.");

    $this->assertEquals($entity->get('field_test')->data, $data, "Data {$entity->get('field_test')->data} is equal to data {$data}.");

    // Verify changing the field value.
    $new_lat = rand(-90, 90) - rand(0, 999999) / 1000000;
    $new_lng = rand(-180, 180) - rand(0, 999999) / 1000000;
    $new_data = ['an_array'];
    $entity->get('field_test')->lat = $new_lat;
    $entity->get('field_test')->lng = $new_lng;
    $entity->get('field_test')->data = $new_data;
    $this->assertEquals($entity->get('field_test')->lat, $new_lat, "Lat {$entity->get('field_test')->lat} is equal to new lat {$new_lat}.");
    $this->assertEquals($entity->get('field_test')->lng, $new_lng, "Lng {$entity->get('field_test')->lng} is equal to new lng {$new_lng}.");
    $this->assertEquals($entity->get('field_test')->data, $new_data, "Data is correctly updated to new data.");

    // Read changed entity and assert changed values.
    $entity->save();
    $entity = $entityTestStorage->load($id);
    $this->assertEquals($entity->get('field_test')->lat, $new_lat, "Lat {$entity->get('field_test')->lat} is equal to new lat {$new_lat}.");
    $this->assertEquals($entity->get('field_test')->lng, $new_lng, "Lng {$entity->get('field_test')->lng} is equal to new lng {$new_lng}.");

    $this->assertEquals(round($entity->get('field_test')->lat_sin, 5), round(sin(deg2rad($new_lat)), 5), "Sine for latitude calculated correctly after change.");
    $this->assertEquals(round($entity->get('field_test')->lat_cos, 5), round(cos(deg2rad($new_lat)), 5), "Cosine for latitude calculated correctly after change.");
    $this->assertEquals(round($entity->get('field_test')->lng_rad, 5), round(deg2rad($new_lng), 5), "Radian value for longitude calculated correctly after change.");

    $this->assertEquals($entity->get('field_test')->data, $new_data, "Data is correctly updated to new data.");
  }

}
