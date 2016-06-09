<?php

/**
 * @file
 * Contains \Drupal\Tests\page_manager\Unit\PageAccessTest.
 */

namespace Drupal\Tests\page_manager\Unit;

use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Plugin\Context\ContextHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\page_manager\Entity\PageAccess;
use Drupal\page_manager\PageInterface;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;

/**
 * Tests access for Page entities.
 *
 * @coversDefaultClass \Drupal\page_manager\Entity\PageAccess
 *
 * @group PageManager
 */
class PageAccessTest extends UnitTestCase {

  /**
   * The context handler.
   *
   * @var \Drupal\Core\Plugin\Context\ContextHandlerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $contextHandler;

  /**
   * @var \Drupal\Core\Entity\EntityTypeInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityType;

  /**
   * @var \Drupal\Core\Cache\Context\CacheContextsManager|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $cacheContextsManager;

  /**
   * @var \Drupal\Core\Entity\EntityAccessControlHandlerInterface
   */
  protected $pageAccess;

  /**
   * @covers ::__construct
   */
  public function setUp() {
    parent::setUp();
    $this->contextHandler = $this->prophesize(ContextHandlerInterface::class);
    $this->entityType = $this->prophesize(EntityTypeInterface::class);

    $module_handler = $this->prophesize(ModuleHandlerInterface::class);
    $module_handler->invokeAll(Argument::cetera())->willReturn([]);

    $this->pageAccess = new PageAccess($this->entityType->reveal(), $this->contextHandler->reveal());
    $this->pageAccess->setModuleHandler($module_handler->reveal());

    $this->cacheContextsManager = $this->prophesize(CacheContextsManager::class);
    $container = new ContainerBuilder();
    $container->set('cache_contexts_manager', $this->cacheContextsManager->reveal());
    \Drupal::setContainer($container);
  }

  /**
   * @covers ::checkAccess
   */
  public function testAccessView() {
    $page = $this->prophesize(PageInterface::class);

    $page->getContexts()->willReturn([]);
    $page->getAccessConditions()->willReturn([]);
    $page->getAccessLogic()->willReturn('and');
    $page->status()->willReturn(TRUE);
    $page->language()->willReturn($this->prophesize(LanguageInterface::class)->reveal());

    $page->uuid()->shouldBeCalled();
    $page->getEntityTypeId()->shouldBeCalled();

    $account = $this->prophesize(AccountInterface::class);

    $this->assertTrue($this->pageAccess->access($page->reveal(), 'view', $account->reveal()));
  }

  /**
   * @covers ::checkAccess
   */
  public function testAccessViewDisabled() {
    $page = $this->prophesize(PageInterface::class);
    $page->status()->willReturn(FALSE);
    $page->getCacheTags()->willReturn(['page:1']);
    $page->getCacheContexts()->willReturn([]);
    $page->getCacheMaxAge()->willReturn(0);
    $page->language()->willReturn($this->prophesize(LanguageInterface::class)->reveal());

    $page->uuid()->shouldBeCalled();
    $page->getEntityTypeId()->shouldBeCalled();

    $account = $this->prophesize(AccountInterface::class);

    $this->assertFalse($this->pageAccess->access($page->reveal(), 'view', $account->reveal()));
  }

  /**
   * @covers ::checkAccess
   *
   * @dataProvider providerTestAccessDelete
   */
  public function testAccessDelete($is_new, $expected) {
    $this->entityType->getAdminPermission()->willReturn('test permission');

    $page = $this->prophesize(PageInterface::class);
    $page->isNew()->willReturn($is_new);
    $page->language()->willReturn($this->prophesize(LanguageInterface::class)->reveal());

    $page->uuid()->shouldBeCalled();
    $page->getEntityTypeId()->shouldBeCalled();

    // Ensure that the cache tag is added for the temporary conditions.
    if ($is_new) {
      $page->getCacheTags()->willReturn(['page:1']);
      $page->getCacheContexts()->willReturn([]);
      $page->getCacheMaxAge()->willReturn(0);
    }
    else {
      $this->cacheContextsManager->assertValidTokens(['user.permissions'])->willReturn(TRUE);
    }

    $account = $this->prophesize(AccountInterface::class);
    $account->hasPermission('test permission')->willReturn(TRUE);
    $account->id()->shouldBeCalled();

    $this->assertSame($expected, $this->pageAccess->access($page->reveal(), 'delete', $account->reveal()));
  }

  /**
   * Provides data for testAccessDelete().
   */
  public function providerTestAccessDelete() {
    $data = [];
    $data[] = [TRUE, FALSE];
    $data[] = [FALSE, TRUE];
    return $data;
  }

}
