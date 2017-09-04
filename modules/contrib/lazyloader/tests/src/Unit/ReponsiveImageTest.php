<?php

namespace Drupal\Tests\lazyloader\Unit;

use Drupal\lazyloader\ResponsiveImage;

/**
 * @coversDefaultClass \Drupal\lazyloader\ResponsiveImage
 * @group lazyloader
 */
class ReponsiveImageTest extends \PHPUnit_Framework_TestCase {

  /**
   * @covers ::count
   * @covers ::parse
   */
  public function testSingleImage() {
    $string = 'mall.jpg 500w';
    $image = ResponsiveImage::parse($string);
    $this->assertCount(1, $image);
    $this->assertEquals('mall.jpg', $image->get(0)->uri);
    $this->assertEquals('500', $image->get(0)->width);
    $this->assertNull($image->get(0)->density);
    return $image;
  }

  /**
   * @depends testSingleImage
   */
  public function testSingleImageToString(ResponsiveImage $image) {
    $string = 'mall.jpg 500w';
    $this->assertEquals($string, $image->__toString());
  }

  /**
   * @covers ::count
   * @covers ::parse
   */
  public function testSingleImageWithDensity() {
    $string = 'mall.jpg 2x';
    $image = ResponsiveImage::parse($string);
    $this->assertCount(1, $image);
    $this->assertEquals('mall.jpg', $image->get(0)->uri);
    $this->assertNull($image->get(0)->width);
    $this->assertEquals('2', $image->get(0)->density);
    $this->assertEquals($string, $image->__toString());
    return $image;
  }

  /**
   * @depends testSingleImageWithDensity
   */
  public function testSingleImageWithDensityToString(ResponsiveImage $image) {
    $string = 'mall.jpg 2x';
    $this->assertEquals($string, $image->__toString());
  }

  /**
   * @covers ::count
   * @covers ::parse
   */
  public function testMultipleImages() {
    $string = 'small.jpg 500w,
		medium.jpg 3x,

  big.jpg 1024w';
    $image = ResponsiveImage::parse($string);
    $this->assertCount(3, $image);
    $this->assertEquals('small.jpg', $image->get(0)->uri);
    $this->assertEquals('medium.jpg', $image->get(1)->uri);
    $this->assertEquals('big.jpg', $image->get(2)->uri);

    $this->assertEquals('500', $image->get(0)->width);
    $this->assertNull($image->get(1)->width);
    $this->assertEquals('1024', $image->get(2)->width);

    $this->assertNull($image->get(0)->density);
    $this->assertEquals('3', $image->get(1)->density);
    $this->assertNull($image->get(2)->density);

    return $image;
  }

  /**
   * @depends testMultipleImages
   */
  public function testMultipleImagesToString(ResponsiveImage $image) {
    $string = 'small.jpg 500w, medium.jpg 3x, big.jpg 1024w';
    $this->assertEquals($string, $image->__toString());
  }

}
