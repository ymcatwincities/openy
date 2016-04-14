<?php

/**
 * @file
 * Contains \Drupal\Tests\page_manager\Unit\PageContextTestBase.
 */

namespace Drupal\Tests\page_manager\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\TypedData\TypedDataManager;
use Drupal\page_manager\Event\PageManagerContextEvent;
use Drupal\page_manager\PageInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Provides a base class for testing page context classes.
 */
abstract class PageContextTestBase extends UnitTestCase {

  /**
   * The typed data manager.
   *
   * @var \Drupal\Core\TypedData\TypedDataManager|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $typedDataManager;

  /**
   * The page entity.
   *
   * @var \Drupal\page_manager\PageInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $page;

  /**
   * The event.
   *
   * @var \Drupal\page_manager\Event\PageManagerContextEvent
   */
  protected $event;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->typedDataManager = $this->prophesize(TypedDataManager::class);

    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    $container->set('typed_data_manager', $this->typedDataManager->reveal());
    \Drupal::setContainer($container);

    $this->page = $this->prophesize(PageInterface::class);

    $this->event = new PageManagerContextEvent($this->page->reveal());
  }

}
