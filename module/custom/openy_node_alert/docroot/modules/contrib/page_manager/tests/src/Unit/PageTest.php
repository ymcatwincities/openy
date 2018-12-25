<?php

/**
 * @file
 * Contains \Drupal\Tests\page_manager\Unit\PageTest.
 */

namespace Drupal\Tests\page_manager\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\page_manager\Entity\Page;
use Drupal\page_manager\Event\PageManagerContextEvent;
use Drupal\page_manager\Event\PageManagerEvents;
use Drupal\page_manager\PageVariantInterface;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Tests the Page entity.
 *
 * @coversDefaultClass \Drupal\page_manager\Entity\Page
 *
 * @group PageManager
 */
class PageTest extends UnitTestCase {

  /**
   * @var \Drupal\page_manager\Entity\Page
   */
  protected $page;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->page = new Page(['id' => 'the_page'], 'page');
  }

  /**
   * @covers ::getVariants
   */
  public function testGetVariants() {
    $variant1 = $this->prophesize(PageVariantInterface::class);
    $variant1->id()->willReturn('variant1');
    $variant1->getWeight()->willReturn(0);
    $variant2 = $this->prophesize(PageVariantInterface::class);
    $variant2->id()->willReturn('variant2');
    $variant2->getWeight()->willReturn(-10);

    $entity_storage = $this->prophesize(EntityStorageInterface::class);
    $entity_storage
      ->loadByProperties(['page' => 'the_page'])
      ->willReturn(['variant1' => $variant1->reveal(), 'variant2' => $variant2->reveal()])
      ->shouldBeCalledTimes(1);

    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);
    $entity_type_manager->getStorage('page_variant')->willReturn($entity_storage);

    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $entity_type_manager->reveal());
    \Drupal::setContainer($container);

    $variants = $this->page->getVariants();
    $this->assertSame(['variant2' => $variant2->reveal(), 'variant1' => $variant1->reveal()], $variants);
    $variants = $this->page->getVariants();
    $this->assertSame(['variant2' => $variant2->reveal(), 'variant1' => $variant1->reveal()], $variants);
  }

  /**
   * @covers ::addVariant
   */
  public function testAddVariant() {
    $variant1 = $this->prophesize(PageVariantInterface::class);
    $variant1->id()->willReturn('variant1');
    $variant1->getWeight()->willReturn(0);

    $variant2 = $this->prophesize(PageVariantInterface::class);
    $variant2->id()->willReturn('variant2');
    $variant2->getWeight()->willReturn(-10);

    $variant3 = $this->prophesize(PageVariantInterface::class);
    $variant3->id()->willReturn('variant3');
    $variant3->getWeight()->willReturn(-5);

    $entity_storage = $this->prophesize(EntityStorageInterface::class);
    $entity_storage
      ->loadByProperties(['page' => 'the_page'])
      ->willReturn([
        'variant1' => $variant1->reveal(),
        'variant2' => $variant2->reveal(),
      ])
      ->shouldBeCalledTimes(1);

    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);
    $entity_type_manager->getStorage('page_variant')->willReturn($entity_storage);

    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $entity_type_manager->reveal());
    \Drupal::setContainer($container);

    // Check that Variant 1 and 2 are in the right order.
    $variants = $this->page->getVariants();
    $this->assertSame([
      'variant2' => $variant2->reveal(),
      'variant1' => $variant1->reveal(),
    ], $variants);

    // Add Variant 3.
    $this->page->addVariant($variant3->reveal());

    // Check that Variant 1, 2 and 3 are in the right order.
    $variants = $this->page->getVariants();
    $this->assertSame([
      'variant2' => $variant2->reveal(),
      'variant3' => $variant3->reveal(),
      'variant1' => $variant1->reveal(),
    ], $variants);
  }

  /**
   * @covers ::removeVariant
   */
  public function testRemoveVariant() {
    $variant1 = $this->prophesize(PageVariantInterface::class);
    $variant1->id()->willReturn('variant1');
    $variant1->getWeight()->willReturn(0);
    $variant1->delete()->shouldBeCalledTimes(1);

    $variant2 = $this->prophesize(PageVariantInterface::class);
    $variant2->id()->willReturn('variant2');
    $variant2->getWeight()->willReturn(-10);

    $entity_storage = $this->prophesize(EntityStorageInterface::class);
    $entity_storage
      ->loadByProperties(['page' => 'the_page'])
      ->willReturn([
        'variant1' => $variant1->reveal(),
        'variant2' => $variant2->reveal(),
      ])
      ->shouldBeCalledTimes(1);

    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);
    $entity_type_manager->getStorage('page_variant')->willReturn($entity_storage);

    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $entity_type_manager->reveal());
    \Drupal::setContainer($container);

    // Check that Variant 1 and 2 are returned.
    $variants = $this->page->getVariants();
    $this->assertSame([
      'variant2' => $variant2->reveal(),
      'variant1' => $variant1->reveal(),
    ], $variants);

    // Remove Variant 1.
    $this->page->removeVariant($variant1->reveal()->id());

    // Check that Variant 1 has been removed.
    $variants = $this->page->getVariants();
    $this->assertSame([
      'variant2' => $variant2->reveal(),
    ], $variants);
  }

  /**
   * @covers ::addContext
   */
  public function testAddContext() {
    $context = new Context(new ContextDefinition('bar'));
    $this->page->addContext('foo', $context);
    $contexts = $this->page->getContexts();
    $this->assertSame(['foo' => $context], $contexts);
  }

  /**
   * @covers ::getContexts
   */
  public function testGetContexts() {
    $context = new Context(new ContextDefinition('bar'));

    $event_dispatcher = $this->prophesize(EventDispatcherInterface::class);
    $event_dispatcher->dispatch(PageManagerEvents::PAGE_CONTEXT, Argument::type(PageManagerContextEvent::class))
      ->will(function ($args) use ($context) {
        $args[1]->getPage()->addContext('foo', $context);
      });

    $container = new ContainerBuilder();
    $container->set('event_dispatcher', $event_dispatcher->reveal());
    \Drupal::setContainer($container);

    $contexts = $this->page->getContexts();
    $this->assertSame(['foo' => $context], $contexts);
  }

  /**
   * @covers ::filterParameters
   */
  public function testFilterParameters() {
    // Changing filters clears cached contexts on variants so we have to setup
    // some variants for our page.
    $variant1 = $this->prophesize(PageVariantInterface::class);
    $variant1->id()->willReturn('variant1');
    $variant1->getWeight()->willReturn(0);
    $variant1->resetCollectedContexts()->willReturn($variant1->reveal());
    $variant2 = $this->prophesize(PageVariantInterface::class);
    $variant2->id()->willReturn('variant2');
    $variant2->getWeight()->willReturn(-10);
    $variant2->resetCollectedContexts()->willReturn($variant2->reveal());

    $entity_storage = $this->prophesize(EntityStorageInterface::class);
    $entity_storage
      ->loadByProperties(['page' => 'the_page'])
      ->willReturn(['variant1' => $variant1->reveal(), 'variant2' => $variant2->reveal()])
      ->shouldBeCalledTimes(1);

    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);
    $entity_type_manager->getStorage('page_variant')->willReturn($entity_storage);

    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $entity_type_manager->reveal());
    \Drupal::setContainer($container);

    $parameters = [
      'foo' => [
        'machine_name' => 'foo',
        'type' => 'integer',
        'label' => 'Foo',
      ],
      'bar' => [
        'machine_name' => 'bar',
        'type' => '',
        'label' => '',
      ],
      'baz' => [
        'machine_name' => 'baz',
        'type' => 'integer',
        'label' => 'Baz',
      ]
    ];
    $page = new Page(['id' => 'the_page', 'parameters' => $parameters, 'path' => 'test/{foo}/{bar}'], 'page');

    $expected = $parameters;
    unset($expected['baz']);
    $this->assertEquals($expected, $page->getParameters());

    $method = new \ReflectionMethod($page, 'filterParameters');
    $method->setAccessible(TRUE);
    $method->invoke($page);

    $expected = [
      'foo' => [
        'machine_name' => 'foo',
        'type' => 'integer',
        'label' => 'Foo',
      ],
    ];
    $this->assertEquals($expected, $page->get('parameters'));
  }

}
