<?php

/**
 * @file
 * Contains \Drupal\Tests\page_manager\Kernel\PageVariantPageEntityTest.
 */

namespace Drupal\Tests\page_manager\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\page_manager\Entity\Page;
use Drupal\page_manager\Entity\PageVariant;

/**
 * Tests storing an page entity on a page variant.
 *
 * @group PageManager
 */
class PageVariantPageEntityTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['page_manager'];

  /**
   * Tests that a page gets cached on the page variant.
   */
  public function testPageGetsCached() {
    /* @var \Drupal\page_manager\PageInterface $page */
    $page = Page::create(['id' => 'test_page']);
    $page->save();

    /* @var \Drupal\page_manager\PageVariantInterface $page_variant */
    $page_variant = PageVariant::create([
      'id' => 'test_page_variant',
      'page' => 'test_page',
    ]);

    // Get the page from the variant.
    $page_first = $page_variant->getPage();
    $this->assertNotEmpty($page_first);
    $page_second = $page_variant->getPage();
    $this->assertEquals(spl_object_hash($page_first), spl_object_hash($page_second));
  }

  /**
   * Tests that a an unsaved page can be set against a page variant.
   */
  public function testUnsavedPage() {
    /* @var \Drupal\page_manager\PageInterface $page */
    $page = Page::create(['id' => 'test_page']);

    /* @var \Drupal\page_manager\PageVariantInterface $page_variant */
    $page_variant = PageVariant::create([
      'id' => 'test_page_variant',
      'page' => 'test_page',
    ]);
    $page_variant->setPageEntity($page);

    // Get the page from the variant.
    $page_result = $page_variant->getPage();
    $this->assertEquals($page, $page_result);
  }

  /**
   * Tests that a page gets cached on the page variant.
   */
  public function testChangePageId() {
    /* @var \Drupal\page_manager\PageVariantInterface $page_variant */
    $page_variant = PageVariant::create(['id' => 'test_page_variant']);

    // Check the page gets set correctly.
    /* @var \Drupal\page_manager\PageInterface $page */
    $page1 = Page::create(['id' => 'test_page_1']);
    $page_variant->setPageEntity($page1);
    $this->assertEquals('test_page_1', $page_variant->get('page'));

    // Check the page gets changed correctly.
    /* @var \Drupal\page_manager\PageInterface $page */
    $page2 = Page::create(['id' => 'test_page_2']);
    $page_variant->setPageEntity($page2);
    $this->assertEquals('test_page_2', $page_variant->get('page'));
  }

}
