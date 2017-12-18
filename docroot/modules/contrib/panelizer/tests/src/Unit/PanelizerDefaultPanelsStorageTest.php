<?php

namespace Drupal\Tests\panelizer\Unit;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\ctools\Context\AutomaticContext;
use Drupal\panelizer\Exception\PanelizerException;
use Drupal\panelizer\Panelizer;
use Drupal\panelizer\Plugin\PanelsStorage\PanelizerDefaultPanelsStorage;
use Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the PanelizerDefaultPanelsStorage service.
 *
 * @coversDefaultClass \Drupal\panelizer\Plugin\PanelsStorage\PanelizerDefaultPanelsStorage
 *
 * @group panelizer
 */
class PanelizerDefaultPanelsStorageTest extends UnitTestCase {

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

    $this->panelsStorage = $this->getMockBuilder(PanelizerDefaultPanelsStorage::class)
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
  public function testLoadEmptyContext() {
    $entity_context = $this->prophesize(AutomaticContext::class);

    $panels_display = $this->prophesize(PanelsDisplayVariant::class);
    $panels_display->setContexts([
      '@panelizer.entity_context:entity' => $entity_context->reveal(),
    ])->shouldBeCalled();

    $this->panelizer->getDefaultPanelsDisplay('default', 'entity_type_id', 'bundle', 'view_mode', NULL)
      ->willReturn($panels_display->reveal());

    $this->panelizer
      ->getDisplayStaticContexts('default', 'entity_type_id', 'bundle', 'view_mode')
      ->willReturn([]);

    $this->panelsStorage->method('getEntityContext')
      ->with($this->equalTo('entity_type_id'), $this->isNull())
      ->willReturn([
        '@panelizer.entity_context:entity' => $entity_context->reveal(),
      ]);

    $this->assertSame($panels_display->reveal(), $this->panelsStorage->load('entity_type_id:bundle:view_mode:default'));
  }

  /**
   * @covers ::load
   */
  public function testLoadWithContextValue() {
    $entity_context = $this->prophesize(AutomaticContext::class);

    $panels_display = $this->prophesize(PanelsDisplayVariant::class);
    $panels_display->setContexts([
      '@panelizer.entity_context:entity' => $entity_context->reveal(),
    ])->shouldBeCalled();

    $this->panelizer->getDefaultPanelsDisplay('default', 'entity_type_id', 'bundle', 'view_mode', NULL)
      ->willReturn($panels_display->reveal());

    $this->panelizer
      ->getDisplayStaticContexts('default', 'entity_type_id', 'bundle', 'view_mode')
      ->willReturn([]);

    $entity = $this->prophesize(EntityInterface::class);
    $entity->bundle()->willReturn("bundle");
    $this->storage->load('123')->willReturn($entity->reveal())->shouldBeCalled();

    $this->panelsStorage->method('getEntityContext')
      ->with($this->equalTo('entity_type_id'), $entity->reveal())
      ->willReturn([
        '@panelizer.entity_context:entity' => $entity_context->reveal(),
      ]);

    $this->assertSame($panels_display->reveal(), $this->panelsStorage->load('*entity_type_id:123:view_mode:default'));
  }

  /**
   * @covers ::load
   */
  public function testLoadDoesntExist() {
    $this->panelizer->getDefaultPanelsDisplay('default', 'entity_type_id', 'bundle', 'view_mode', NULL)
      ->willReturn(NULL);

    $this->assertSame(NULL, $this->panelsStorage->load('entity_type_id:bundle:view_mode:default'));
  }

  /**
   * @covers ::load
   */
  public function testLoadNoEntity() {
    $this->storage->load('123')->willReturn(NULL)->shouldBeCalled();

    $this->panelizer->getDefaultPanelsDisplay('default', 'entity_type_id', 'bundle', 'view_mode', NULL)
      ->shouldNotBeCalled();

    $this->assertSame(NULL, $this->panelsStorage->load('*entity_type_id:123:view_mode:default'));
  }

  /**
   * @covers ::save
   */
  public function testSaveSuccessful() {
    $panels_display = $this->prophesize(PanelsDisplayVariant::class);
    $panels_display->getStorageId()->willReturn('entity_type_id:bundle:view_mode:default');

    $this->panelizer->setDefaultPanelsDisplay('default', 'entity_type_id', 'bundle', 'view_mode', $panels_display->reveal())
      ->shouldBeCalled();

    $this->panelsStorage->save($panels_display->reveal());
  }

  /**
   * @covers ::save
   *
   * @expectedException \Exception
   * @expectedExceptionMessage Couldn't find Panelizer default to store Panels display
   */
  public function testSaveDoesntExist() {
    $panels_display = $this->prophesize(PanelsDisplayVariant::class);
    $panels_display->getStorageId()->willReturn('entity_type_id:bundle:view_mode:default');

    $this->panelizer->setDefaultPanelsDisplay('default', 'entity_type_id', 'bundle', 'view_mode', $panels_display->reveal())
      ->willThrow(new PanelizerException());

    $this->panelsStorage->save($panels_display->reveal());
  }

  /**
   * @covers ::save
   *
   * @expectedException \Exception
   * @expectedExceptionMessage Couldn't find Panelizer default to store Panels display
   */
  public function testSaveNoEntity() {
    $panels_display = $this->prophesize(PanelsDisplayVariant::class);
    $panels_display->getStorageId()->willReturn('*entity_type_id:123:view_mode:default');

    $this->storage->load('123')->willReturn(NULL)->shouldBeCalled();

    $this->panelizer->setDefaultPanelsDisplay('default', 'entity_type_id', 'bundle', 'view_mode', $panels_display->reveal())
      ->shouldNotBeCalled();

    $this->panelsStorage->save($panels_display->reveal());
  }

  /**
   * @covers ::access
   */
  public function testAccessRead() {
    $panels_display = $this->prophesize(PanelsDisplayVariant::class);
    $account = $this->prophesize(AccountInterface::class);

    $this->panelizer->getDefaultPanelsDisplay('default', 'entity_type_id', 'bundle', 'view_mode')
      ->willReturn($panels_display->reveal());
    $this->panelizer->hasDefaultPermission()->shouldNotBeCalled();

    $this->assertEquals(AccessResult::allowed(), $this->panelsStorage->access('entity_type_id:bundle:view_mode:default', 'read', $account->reveal()));
  }

  /**
   * @covers ::access
   */
  public function testAccessNotFound() {
    $account = $this->prophesize(AccountInterface::class);

    $this->panelizer->getDefaultPanelsDisplay('default', 'entity_type_id', 'bundle', 'view_mode')
      ->willReturn(NULL);
    $this->panelizer->hasDefaultPermission()->shouldNotBeCalled();

    $this->assertEquals(AccessResult::forbidden(), $this->panelsStorage->access('entity_type_id:bundle:view_mode:default', 'read', $account->reveal()));
  }

  /**
   * @covers ::access
   */
  public function testAccessNoEntity() {
    $account = $this->prophesize(AccountInterface::class);

    $this->storage->load('123')->willReturn(NULL)->shouldBeCalled();

    $this->panelizer->getDefaultPanelsDisplay('default', 'entity_type_id', 'bundle', 'view_mode')
      ->shouldNotBeCalled();

    $this->assertEquals(AccessResult::forbidden(), $this->panelsStorage->access('*entity_type_id:123:view_mode:default', 'read', $account->reveal()));
  }

  /**
   * @covers ::access
   */
  public function testAccessChangeContent() {
    $panels_display = $this->prophesize(PanelsDisplayVariant::class);
    $account = $this->prophesize(AccountInterface::class);

    $this->panelizer->getDefaultPanelsDisplay('default', 'entity_type_id', 'bundle', 'view_mode')
      ->willReturn($panels_display->reveal());
    $this->panelizer->hasDefaultPermission('change content', 'entity_type_id', 'bundle', 'view_mode', 'default', $account->reveal())
      ->willReturn(TRUE);

    $this->assertEquals(AccessResult::allowed(), $this->panelsStorage->access('entity_type_id:bundle:view_mode:default', 'update', $account->reveal()));
    $this->assertEquals(AccessResult::allowed(), $this->panelsStorage->access('entity_type_id:bundle:view_mode:default', 'delete', $account->reveal()));
    $this->assertEquals(AccessResult::allowed(), $this->panelsStorage->access('entity_type_id:bundle:view_mode:default', 'create', $account->reveal()));
  }

  /**
   * @covers ::access
   */
  public function testAccessChangeLayout() {
    $panels_display = $this->prophesize(PanelsDisplayVariant::class);
    $account = $this->prophesize(AccountInterface::class);

    $this->panelizer->getDefaultPanelsDisplay('default', 'entity_type_id', 'bundle', 'view_mode')
      ->willReturn($panels_display->reveal());
    $this->panelizer->hasDefaultPermission('change layout', 'entity_type_id', 'bundle', 'view_mode', 'default', $account->reveal())
      ->willReturn(TRUE);

    $this->assertEquals(AccessResult::allowed(), $this->panelsStorage->access('entity_type_id:bundle:view_mode:default', 'change layout', $account->reveal()));
  }

}
