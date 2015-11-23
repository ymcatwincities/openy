<?php

/**
 * @file
 * Contains \Drupal\crop\Tests\CropCRUDTest.
 */

namespace Drupal\crop\Tests;

use Drupal\Component\Utility\SafeMarkup;

/**
 * Tests the crop entity CRUD operations.
 *
 * @group crop
 */
class CropCRUDTest extends CropUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['user', 'image', 'crop', 'file', 'system'];

  /**
   * Tests crop type save.
   */
  public function testCropTypeSave() {
    $values = [
      'id' => $this->randomMachineName(),
      'label' => $this->randomString(),
      'description' => $this->randomGenerator->sentences(8),
    ];
    $crop_type = $this->cropTypeStorage->create($values);

    try {
      $crop_type->save();
      $this->assertTrue(TRUE, 'Crop type saved correctly.');
    }
    catch (\Exception $exception) {
      $this->assertTrue(FALSE, 'Crop type not saved correctly.');
    }

    $loaded = $this->container->get('config.factory')->get('crop.type.' . $values['id'])->get();
    foreach ($values as $key => $value) {
      $this->assertEqual($loaded[$key], $value, SafeMarkup::format('Correct value for @field found.', ['@field' => $key]));
    }
  }

  /**
   * Tests crop save.
   */
  public function testCropSave() {
    // Test file.
    $file = $this->getTestFile();
    $file->save();

    /** @var \Drupal\crop\CropInterface $crop */
    $values = [
      'type' => $this->cropType->id(),
      'entity_id' => $file->id(),
      'entity_type' => $file->getEntityTypeId(),
      'x' => '100',
      'y' => '150',
      'width' => '200',
      'height' => '250',
      'image_style' => $this->testStyle->id(),
    ];
    $crop = $this->cropStorage->create($values);

    try {
      $crop->save();
      $this->assertTrue(TRUE, 'Crop saved correctly.');
    }
    catch (\Exception $exception) {
      $this->assertTrue(FALSE, 'Crop not saved correctly.');
    }

    $loaded_crop = $this->cropStorage->loadUnchanged(1);
    foreach ($values as $key => $value) {
      switch ($key) {
        case 'image_style':
        case 'type':
          $this->assertEqual($loaded_crop->{$key}->target_id, $value, SafeMarkup::format('Correct value for @field found.', ['@field' => $key]));
          break;

        default:
          $this->assertEqual($loaded_crop->{$key}->value, $value, SafeMarkup::format('Correct value for @field found.', ['@field' => $key]));
          break;
      }
    }

  }

}
