<?php

/**
 * @file
 * Tests for Image Widget Crop.
 */

namespace Drupal\image_widget_crop\Tests;


use Drupal\KernelTests\KernelTestBase;

/**
 * Minimal test case for the image_widget_crop module.
 *
 * @group image_widget_crop
 *
 * @ingroup media
 */
class ImageWidgetCropTest extends KernelTestBase {

  /**
   * Just make sure a test exists so that the Travis test suite doesn't crash.
   */
  public function testNothing() {
    $this->assertTrue('Just ensuring the test group exists for Travis CI not to crash');
  }

}
