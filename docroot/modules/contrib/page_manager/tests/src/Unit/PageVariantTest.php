<?php

/**
 * @file
 * Contains \Drupal\Tests\page_manager\Unit\PageVariantTest.
 */

namespace Drupal\Tests\page_manager\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\page_manager\ContextMapperInterface;
use Drupal\page_manager\Entity\PageVariant;
use Drupal\page_manager\PageInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\page_manager\Entity\PageVariant
 *
 * @group PageManager
 */
class PageVariantTest extends UnitTestCase {

  /**
   * @var \Drupal\page_manager\Entity\PageVariant
   */
  protected $pageVariant;

  /**
   * @var \Drupal\page_manager\PageInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $page;

  /**
   * @var \Drupal\page_manager\ContextMapperInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $contextMapper;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->pageVariant = new PageVariant(['id' => 'the_page_variant', 'page' => 'the_page'], 'page_variant');
    $this->page = $this->prophesize(PageInterface::class);

    $entity_storage = $this->prophesize(EntityStorageInterface::class);
    $entity_storage->load('the_page')->willReturn($this->page->reveal());

    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);
    $entity_type_manager->getStorage('page')->willReturn($entity_storage);

    $this->contextMapper = $this->prophesize(ContextMapperInterface::class);

    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $entity_type_manager->reveal());
    $container->set('page_manager.context_mapper', $this->contextMapper->reveal());
    \Drupal::setContainer($container);
  }

  /**
   * @covers ::getContexts
   * @dataProvider providerTestGetContexts
   */
  public function testGetContexts($static_contexts, $page_contexts, $expected) {
    $this->contextMapper->getContextValues([])->willReturn($static_contexts)->shouldBeCalledTimes(1);
    $this->page->getContexts()->willReturn($page_contexts)->shouldBeCalledTimes(1);

    $contexts = $this->pageVariant->getContexts();
    $this->assertSame($expected, $contexts);
    $contexts = $this->pageVariant->getContexts();
    $this->assertSame($expected, $contexts);
  }

  public function providerTestGetContexts() {
    $data = [];
    $data['empty'] = [
      [],
      [],
      [],
    ];
    $data['additive'] = [
      ['static' => 'static'],
      ['page' => 'page'],
      ['page' => 'page', 'static' => 'static'],
    ];
    $data['conflicting'] = [
      ['foo' => 'static'],
      ['foo' => 'page'],
      ['foo' => 'page'],
    ];
    return $data;
  }

  /**
   * @covers ::getContexts
   * @covers ::removeStaticContext
   */
  public function testGetContextsAfterReset() {
    $this->contextMapper->getContextValues([])->willReturn([])->shouldBeCalledTimes(2);
    $this->page->getContexts()->willReturn([])->shouldBeCalledTimes(2);

    $expected = [];
    $contexts = $this->pageVariant->getContexts();
    $this->assertSame($expected, $contexts);
    $this->pageVariant->removeStaticContext('anything');
    $contexts = $this->pageVariant->getContexts();
    $this->assertSame($expected, $contexts);
  }

}
