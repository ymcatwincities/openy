<?php

namespace Drupal\Tests\panels\Unit\panels_ipe;

use Drupal\panels_ipe\Helpers\RemoveBlockRequestHandler;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Tests for Drupal\panels_ipe\Helpers\RemoveBlockRequestHandler.
 *
 * @group Panels IPE
 */
class RemoveBlockRequestHandlerTest extends RequestHandlerTestBase {

  public function setUp() {
    parent::setUp();
    $this->sut = new RemoveBlockRequestHandler($this->moduleHandler, $this->panelsStore, $this->tempStore);
  }

  /**
   * @test
   */
  public function removeBlockRequestRemovesTheBlock() {
    $this->panelsDisplay->expects($this->once())->method('removeBlock');
    $this->sut->handleRequest($this->panelsDisplay, $this->createRequest('someblock'));
    $this->assertEquals(new JsonResponse([]), $this->sut->getJsonResponse());
  }

  /**
   * @test
   */
  public function panelsDisplayIsSavedAfterBlockRemoval() {
    $this->panelsStore->expects($this->once())->method('save');
    $this->sut->handleRequest($this->panelsDisplay, $this->createRequest('someblock'));
  }

  /**
   * @test
   */
  public function panelsDisplayIsSavedToTempstoreAfterBlockRemoval() {
    $this->tempStore->expects($this->once())->method('set');
    $this->sut->handleRequest($this->panelsDisplay, $this->createRequest('someblock'), TRUE);
  }

}
