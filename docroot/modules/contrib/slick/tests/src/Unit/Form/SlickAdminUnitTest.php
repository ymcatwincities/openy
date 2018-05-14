<?php

namespace Drupal\Tests\slick\Unit\Form;

use Drupal\Tests\UnitTestCase;
use Drupal\slick\Form\SlickAdmin;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Tests the Slick admin form.
 *
 * @coversDefaultClass \Drupal\slick\Form\SlickAdmin
 * @group slick
 */
class SlickAdminUnitTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->blazyAdminExtended = $this->getMockBuilder('\Drupal\blazy\Dejavu\BlazyAdminExtended')
      ->disableOriginalConstructor()
      ->getMock();
    $this->slickManager = $this->getMock('\Drupal\slick\SlickManagerInterface');
  }

  /**
   * @covers ::create
   * @covers ::__construct
   * @covers ::blazyAdmin
   * @covers ::manager
   */
  public function testBlazyAdminCreate() {
    $container = $this->getMock(ContainerInterface::class);
    $exception = ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE;

    $map = [
      ['blazy.admin.extended', $exception, $this->blazyAdminExtended],
      ['slick.manager', $exception, $this->slickManager],
    ];

    $container->expects($this->any())
      ->method('get')
      ->willReturnMap($map);

    $slickAdmin = SlickAdmin::create($container);
    $this->assertInstanceOf(SlickAdmin::class, $slickAdmin);

    $this->assertInstanceOf('\Drupal\blazy\Dejavu\BlazyAdminExtended', $slickAdmin->blazyAdmin());
    $this->assertInstanceOf('\Drupal\slick\SlickManagerInterface', $slickAdmin->manager());
  }

}
