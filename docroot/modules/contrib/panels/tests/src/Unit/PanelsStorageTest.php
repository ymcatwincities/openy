<?php

/**
 * @file
 * Contains \Drupal\Tests\panels\Unit\PanelsStorageTest.
 */

namespace Drupal\Tests\panels\Unit;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\page_manager\PageVariantInterface;
use Drupal\panels\Plugin\PanelsStorage\PageManagerPanelsStorage;
use Drupal\page_manager\Plugin\DisplayVariant\HttpStatusCodeDisplayVariant;
use Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;

/**
 * Tests the PageManagerPanelsStorage service.
 *
 * @coversDefaultClass \Drupal\panels\Plugin\PanelsStorage\PageManagerPanelsStorage
 *
 * @group PageManager
 */
class PanelsStorageTest extends UnitTestCase {

  /**
   * @var \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $panelsDisplay;

  /**
   * @var \Drupal\page_manager\PageVariantInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $pageVariant;

  /**
   * @var \Drupal\page_manager\PageVariantInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $pageVariantNotPanels;

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $storage;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->panelsDisplay = $this->prophesize(PanelsDisplayVariant::class);

    $this->pageVariant = $this->prophesize(PageVariantInterface::class);
    $this->pageVariant->getVariantPlugin()->willReturn($this->panelsDisplay->reveal());

    $this->pageVariantNotPanels = $this->prophesize(PageVariantInterface::class);
    $this->pageVariantNotPanels->getContexts()->shouldNotBeCalled();

    $non_panels_variant = $this->prophesize(HttpStatusCodeDisplayVariant::class);
    $this->pageVariantNotPanels->getVariantPlugin()->willReturn($non_panels_variant->reveal());

    $this->storage = $this->prophesize(EntityStorageInterface::class);

    $this->entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $this->entityTypeManager->getStorage('page_variant')->willReturn($this->storage->reveal());
  }

  /**
   * @covers ::load
   */
  public function testLoad() {
    // Make sure that the contexts are passed down (or not).
    $this->pageVariant->getContexts()->willReturn([]);
    $this->panelsDisplay->setContexts([])->shouldBeCalledTimes(1);

    $this->storage->load('id_exists')->willReturn($this->pageVariant->reveal());
    $this->storage->load('doesnt_exist')->willReturn(NULL);
    $this->storage->load('not_a_panel')->willReturn($this->pageVariantNotPanels->reveal());

    $panels_storage = new PageManagerPanelsStorage([], '', [], $this->entityTypeManager->reveal());

    // Test the success condition.
    $this->assertSame($this->panelsDisplay->reveal(), $panels_storage->load('id_exists'));

    // Should be NULL if it doesn't exist.
    $this->assertNull($panels_storage->load('doesnt_exist'));

    // Should also be NULL if it's not a PanelsDisplayVariant.
    $this->assertNull($panels_storage->load('not_a_panel'));
  }

  /**
   * @covers ::save
   */
  public function testSaveSuccessful() {
    $test_config = ['my_config' => '123'];
    $this->panelsDisplay->setConfiguration($test_config)->shouldBeCalledTimes(1);
    $this->pageVariant->save()->shouldBeCalledTimes(1);

    $this->storage->load('id_exists')->willReturn($this->pageVariant->reveal());

    $panels_display = $this->prophesize(PanelsDisplayVariant::class);
    $panels_display->getStorageId()->willReturn('id_exists');
    $panels_display->getConfiguration()->willReturn($test_config);

    $panels_storage = new PageManagerPanelsStorage([], '', [], $this->entityTypeManager->reveal());
    $panels_storage->save($panels_display->reveal());
  }

  /**
   * @covers ::save
   *
   * @expectedException \Exception
   * @expectedExceptionMessage Couldn't find page variant to store Panels display
   */
  public function testSaveDoesntExist() {
    $this->panelsDisplay->setConfiguration()->shouldNotBeCalled();
    $this->pageVariant->save()->shouldNotBeCalled();

    $this->storage->load('doesnt_exist')->willReturn(NULL);

    $panels_display = $this->prophesize(PanelsDisplayVariant::class);
    $panels_display->getStorageId()->willReturn('doesnt_exist');
    $panels_display->getConfiguration()->shouldNotBeCalled();

    $panels_storage = new PageManagerPanelsStorage([], '', [], $this->entityTypeManager->reveal());
    $panels_storage->save($panels_display->reveal());
  }

  /**
   * @covers ::save
   *
   * @expectedException \Exception
   * @expectedExceptionMessage Page variant doesn't use a Panels display variant
   */
  public function testSaveNotPanels() {
    $this->storage->load('not_a_panel')->willReturn($this->pageVariantNotPanels->reveal());

    $this->panelsDisplay->setConfiguration(Argument::cetera())->shouldNotBeCalled();
    $this->pageVariant->save()->shouldNotBeCalled();

    $panels_display = $this->prophesize(PanelsDisplayVariant::class);
    $panels_display->getStorageId()->willReturn('not_a_panel');
    $panels_display->getConfiguration()->shouldNotBeCalled();

    $panels_storage = new PageManagerPanelsStorage([], '', [], $this->entityTypeManager->reveal());
    $panels_storage->save($panels_display->reveal());
  }

  /**
   * @covers ::access
   */
  public function testAccess() {
    $this->storage->load('id_exists')->willReturn($this->pageVariant->reveal());
    $this->storage->load('doesnt_exist')->willReturn(NULL);

    $account = $this->prophesize(AccountInterface::class);

    $this->pageVariant->access('read', $account->reveal(), TRUE)->willReturn(AccessResult::allowed());

    $panels_storage = new PageManagerPanelsStorage([], '', [], $this->entityTypeManager->reveal());

    // Test the access condition.
    $this->assertEquals(AccessResult::allowed(), $panels_storage->access('id_exists', 'read', $account->reveal()));

    // Should be forbidden if it doesn't exist.
    $this->assertEquals(AccessResult::forbidden(), $panels_storage->access('doesnt_exist', 'read', $account->reveal()));

    // Test that 'change layout' becomes 'update'.
    $this->pageVariant->access('update', $account->reveal(), TRUE)->willReturn(AccessResult::allowed());
    $this->assertEquals(AccessResult::allowed(), $panels_storage->access('id_exists', 'change layout', $account->reveal()));
  }

}
