<?php

/**
 * @file
 * Contains \Drupal\crop\Tests\CropEffectTest.
 */

namespace Drupal\crop\Tests;

/**
 * Tests the crop image effect.
 *
 * @group crop
 */
class CropEffectTest extends CropUnitTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['user', 'image', 'crop', 'file', 'system'];

  /**
   * Tests manual crop image effect.
   */
  public function testCropEffect() {
    // Create image to be cropped.
    $file = $this->getTestFile();
    $file->save();

    // Create crop.
    $values = [
      'type' => $this->cropType->id(),
      'entity_id' => $file->id(),
      'entity_type' => 'file',
      'uri' => $file->getFileUri(),
      'x' => '190',
      'y' => '120',
      'width' => '50',
      'height' => '50',
      'image_style' => $this->testStyle->id(),
    ];
    /** @var \Drupal\crop\CropInterface $crop */
    $crop = $this->container->get('entity.manager')->getStorage('crop')->create($values);
    $crop->save();

    $derivative_uri = $this->testStyle->buildUri($file->getFileUri());
    $this->testStyle->createDerivative($file->getFileUri(), $derivative_uri);

    $this->assertTrue(file_exists($derivative_uri), 'Image derivative file exists on the filesystem.');

    // Test if cropped version looks like expected. Basically loop pixels,
    // in derivative image and check if they look the same as pixels,
    // in corresponding region on original image.
    $original_image = imagecreatefrompng($file->getFileUri());
    $derivative_image = imagecreatefrompng($derivative_uri);
    $orig_start = $crop->anchor();
    $matches = TRUE;
    for ($x = 0; $x < $values['width']; $x++) {
      for ($y = 0; $y < $values['height']; $y++) {
        if (imagecolorat($derivative_image, $x, $y) != imagecolorat($original_image, $orig_start['x'] + $x, $orig_start['y'] + $y)) {
          $matches = FALSE;
          break;
        }
      }
    }
    $this->assertTrue($matches, 'Cropped image looks the same as region on original.');
  }

}
