<?php

namespace Drupal\Tests\panels\Unit\panels_ipe;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Block\BlockBase;
use Drupal\panels_ipe\Helpers\UpdateLayoutRequestHandler;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Tests for \Drupal\panels_ipe\Helpers\UpdateLayoutRequestHandler.
 *
 * @group Panels IPE
 */
class UpdateLayoutRequestHandlerTest extends RequestHandlerTestBase {

  public function setUp() {
    parent::setUp();
    $this->sut = new UpdateLayoutRequestHandler($this->moduleHandler, $this->panelsStore, $this->tempStore);
  }

  private function getLayoutModel() {
    return [
      'regionCollection' => [
        [
          'name' => 'some_region',
          'blockCollection' => [
            ['uuid' => 'someBlock'],
            ['uuid' => 'someOtherBlock'],
          ],
        ],
      ],
    ];
  }

  private function setPanelsDisplayExpectations() {
    $block = $this->getMockBuilder(BlockBase::class)
      ->disableOriginalConstructor()
      ->getMock();
    $block->expects($this->exactly(4))->method('setConfigurationValue');
    $block->expects($this->exactly(2))
      ->method('getConfiguration')
      ->willReturn([]);

    $this->panelsDisplay->method('getBlock')
      ->willReturn($block);
  }

  /**
   * @test
   */
  public function successfulSaveOperationResultsInEmptyJsonResponse() {
    $this->setPanelsDisplayExpectations();
    $this->sut->handleRequest($this->panelsDisplay, $this->createRequest(Json::encode($this->getLayoutModel())));
    $this->assertEquals(new JsonResponse([]), $this->sut->getJsonResponse());
  }

  /**
   * @test
   */
  public function successfulTempStoreSaveOperationResultsInEmptyJsonResponse() {
    $this->setPanelsDisplayExpectations();
    $this->sut->handleRequest($this->panelsDisplay, $this->createRequest(Json::encode($this->getLayoutModel())), TRUE);
    $this->assertEquals(new JsonResponse([]), $this->sut->getJsonResponse());
  }

  /**
   * @test
   */
  public function updatedLayoutGetsSaved() {
    $this->setPanelsDisplayExpectations();
    $this->panelsStore->expects($this->once())->method('save');
    $this->tempStore->expects($this->once())->method('delete');
    $this->tempStore->expects($this->never())->method('set');

    $this->sut->handleRequest($this->panelsDisplay, $this->createRequest(Json::encode($this->getLayoutModel())));
  }

  /**
   * @test
   */
  public function updatedLayoutGetsSavedToTempStore() {
    $this->setPanelsDisplayExpectations();
    $this->panelsStore->expects($this->never())->method('save');
    $this->tempStore->expects($this->never())->method('delete');
    $this->tempStore->expects($this->once())->method('set');
    $this->sut->handleRequest($this->panelsDisplay, $this->createRequest(Json::encode($this->getLayoutModel())), TRUE);
  }

  /**
   * @test
   */
  public function hookPreSaveGetsCalledBeforeSave() {
    $this->setPanelsDisplayExpectations();
    $this->moduleHandler->expects($this->once())->method('invokeAll');
    $this->sut->handleRequest($this->panelsDisplay, $this->createRequest(Json::encode($this->getLayoutModel())), TRUE);
  }

}
