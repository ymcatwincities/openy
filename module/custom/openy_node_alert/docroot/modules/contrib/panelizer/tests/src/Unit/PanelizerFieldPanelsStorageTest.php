<?php

namespace Drupal\Tests\panelizer\Unit;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\ctools\Context\AutomaticContext;
use Drupal\panelizer\Exception\PanelizerException;
use Drupal\panelizer\Panelizer;
use Drupal\panelizer\Plugin\PanelsStorage\PanelizerFieldPanelsStorage;
use Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the PanelizerFieldPanelsStorage service.
 *
 * @coversDefaultClass \Drupal\panelizer\Plugin\PanelsStorage\PanelizerFieldPanelsStorage
 *
 * @group panelizer
 */
class PanelizerFieldPanelsStorageTest extends UnitTestCase {

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $storage;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\panelizer\PanelizerInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $panelizer;

  /**
   * @var \Drupal\panels\Storage\PanelsStorageInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $panelsStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->storage = $this->prophesize(EntityStorageInterface::class);

    $this->entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $this->entityTypeManager->getStorage('entity_type_id')->willReturn($this->storage->reveal());

    $this->panelizer = $this->prophesize(Panelizer::class);

    $this->panelsStorage = $this->getMockBuilder(PanelizerFieldPanelsStorage::class)
      ->setConstructorArgs([
        [],
        '',
        [],
        $this->entityTypeManager->reveal(),
        $this->panelizer->reveal(),
      ])
      ->setMethods(['getEntityContext'])
      ->getMock();
  }

  /**
   * @covers ::load
   */
  public function testLoad() {
    $entity_context = $this->prophesize(AutomaticContext::class);

    $panels_display = $this->prophesize(PanelsDisplayVariant::class);
    $panels_display->setContexts([
      '@panelizer.entity_context:entity' => $entity_context->reveal(),
    ])->shouldBeCalled();

    $entity = $this->prophesize(FieldableEntityInterface::class);

    $this->panelizer->getPanelsDisplay($entity->reveal(), 'view_mode')
      ->willReturn($panels_display->reveal());

    $this->storage->load('123')->willReturn($entity->reveal())->shouldBeCalled();

    $this->panelsStorage->method('getEntityContext')
      ->with($this->equalTo('entity_type_id'), $entity->reveal())
      ->willReturn($entity_context->reveal());

    $this->assertSame($panels_display->reveal(), $this->panelsStorage->load('entity_type_id:123:view_mode'));
  }

  /**
   * @covers ::load
   */
  public function testLoadRevision() {
    $entity_context = $this->prophesize(AutomaticContext::class);

    $panels_display = $this->prophesize(PanelsDisplayVariant::class);
    $panels_display->setContexts([
      '@panelizer.entity_context:entity' => $entity_context->reveal(),
    ])->shouldBeCalled();

    $entity = $this->prophesize(FieldableEntityInterface::class);

    $this->panelizer->getPanelsDisplay($entity->reveal(), 'view_mode')
      ->willReturn($panels_display->reveal());

    $this->storage->loadRevision('456')->willReturn($entity->reveal())->shouldBeCalled();

    $this->panelsStorage->method('getEntityContext')
      ->with($this->equalTo('entity_type_id'), $entity->reveal())
      ->willReturn($entity_context->reveal());

    $this->assertSame($panels_display->reveal(), $this->panelsStorage->load('entity_type_id:123:view_mode:456'));
  }

  /**
   * @covers ::load
   */
  public function testLoadNoEntity() {
    $this->storage->load('123')->willReturn(NULL)->shouldBeCalled();

    $this->panelizer->getPanelsDisplay()->shouldNotBeCalled();

    $this->assertSame(NULL, $this->panelsStorage->load('entity_type_id:123:view_mode'));
  }

  /**
   * @covers ::load
   */
  public function testLoadNotFound() {
    $entity = $this->prophesize(FieldableEntityInterface::class);

    $this->storage->load('123')->willReturn($entity->reveal());

    $this->panelizer->getPanelsDisplay($entity->reveal(), 'view_mode')
      ->willReturn(NULL);

    $this->assertSame(NULL, $this->panelsStorage->load('entity_type_id:123:view_mode'));
  }

  /**
   * @covers ::save
   */
  public function testSaveSuccessful() {
    $panels_display = $this->prophesize(PanelsDisplayVariant::class);
    $panels_display->getStorageId()->willReturn('entity_type_id:123:view_mode');

    $entity = $this->prophesize(FieldableEntityInterface::class);

    $this->panelizer->setPanelsDisplay($entity->reveal(), 'view_mode', NULL, $panels_display)
      ->shouldBeCalled();

    $this->storage->load('123')->willReturn($entity->reveal())->shouldBeCalled();

    $this->panelsStorage->save($panels_display->reveal());
  }

  /**
   * @covers ::save
   *
   * @expectedException \Exception
   * @expectedExceptionMessage Couldn't find entity to store Panels display on
   */
  public function testSaveNoEntity() {
    $panels_display = $this->prophesize(PanelsDisplayVariant::class);
    $panels_display->getStorageId()->willReturn('entity_type_id:123:view_mode');

    $this->panelizer->setPanelsDisplay()->shouldNotBeCalled();

    $this->storage->load('123')->willReturn(NULL)->shouldBeCalled();

    $this->panelsStorage->save($panels_display->reveal());
  }

  /**
   * @covers ::save
   *
   * @expectedException \Exception
   * @expectedExceptionMessage Save failed
   */
  public function testSaveFailed() {
    $panels_display = $this->prophesize(PanelsDisplayVariant::class);
    $panels_display->getStorageId()->willReturn('entity_type_id:123:view_mode');

    $entity = $this->prophesize(FieldableEntityInterface::class);

    $this->panelizer->setPanelsDisplay($entity->reveal(), 'view_mode', NULL, $panels_display)
      ->willThrow(new PanelizerException("Save failed"));

    $this->storage->load('123')->willReturn($entity->reveal())->shouldBeCalled();

    $this->panelsStorage->save($panels_display->reveal());
  }

  /**
   * @covers ::access
   */
  public function testAccessRead() {
    $panels_display = $this->prophesize(PanelsDisplayVariant::class);
    $account = $this->prophesize(AccountInterface::class);

    $entity = $this->prophesize(FieldableEntityInterface::class);
    $entity->access('view', $account->reveal(), TRUE)
      ->willReturn(AccessResult::allowed());

    $this->storage->load('123')->willReturn($entity->reveal());

    $this->panelizer->getPanelsDisplay($entity->reveal(), 'view_mode')
      ->willReturn($panels_display->reveal());
    $this->panelizer->hasEntityPermission()->shouldNotBeCalled();

    $this->assertEquals(AccessResult::allowed(), $this->panelsStorage->access('entity_type_id:123:view_mode', 'read', $account->reveal()));
  }

  /**
   * @covers ::access
   */
  public function testAccessNoEntity() {
    $account = $this->prophesize(AccountInterface::class);

    $this->storage->load('123')->willReturn(NULL)->shouldBeCalled();

    $this->panelizer->getPanelsDisplay()->shouldNotBeCalled();

    $this->assertEquals(AccessResult::forbidden(), $this->panelsStorage->access('entity_type_id:123:view_mode', 'read', $account->reveal()));
  }


  /**
   * @covers ::access
   */
  public function testAccessChangeContent() {
    $panels_display = $this->prophesize(PanelsDisplayVariant::class);
    $account = $this->prophesize(AccountInterface::class);

    $entity = $this->prophesize(FieldableEntityInterface::class);
    $entity->access('update', $account->reveal(), TRUE)
      ->willReturn(AccessResult::allowed());

    $this->storage->load('123')->willReturn($entity->reveal());

    $this->panelizer->getPanelsDisplay($entity->reveal(), 'view_mode')
      ->willReturn($panels_display->reveal());
    $this->panelizer->hasEntityPermission('change content', $entity->reveal(), 'view_mode', $account->reveal())
      ->willReturn(TRUE);

    $access = $this->panelsStorage->access('entity_type_id:123:view_mode', 'update', $account->reveal());
    $this->assertEquals(AccessResult::allowed(), $access);
  }

  /**
   * @covers ::access
   */
  public function testAccessChangeLayout() {
    $panels_display = $this->prophesize(PanelsDisplayVariant::class);
    $account = $this->prophesize(AccountInterface::class);

    $entity = $this->prophesize(FieldableEntityInterface::class);
    $entity->access('update', $account->reveal(), TRUE)
      ->willReturn(AccessResult::allowed());

    $this->storage->load('123')->willReturn($entity->reveal());

    $this->panelizer->getPanelsDisplay($entity->reveal(), 'view_mode')
      ->willReturn($panels_display->reveal());
    $this->panelizer->hasEntityPermission('change layout', $entity->reveal(), 'view_mode', $account->reveal())
      ->willReturn(TRUE);

    $this->assertEquals(AccessResult::allowed(), $this->panelsStorage->access('entity_type_id:123:view_mode', 'change layout', $account->reveal()));
  }

}
