<?php

/**
 * @file
 * Contains \Drupal\Tests\page_manager\Kernel\SerializationTest.
 */

namespace Drupal\Tests\page_manager\Kernel;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\KernelTests\KernelTestBase;
use Drupal\page_manager\Entity\Page;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\Context;
use Drupal\page_manager\Entity\PageVariant;
use Drupal\page_manager\Plugin\DisplayVariant\HttpStatusCodeDisplayVariant;
use Drupal\page_manager\Plugin\DisplayVariant\PageBlockDisplayVariant;

/**
 * Tests the serialization of the entities we provide.
 *
 * @group PageManager
 */
class SerializationTest extends KernelTestBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['page_manager', 'system', 'user'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // @todo: Remove the silent dependency on user.
    $this->installEntitySchema('user');
  }

  /**
   * Assert that an object successfully serializes and unserializes.
   *
   * @param object $object
   *   The object to serialize.
   * @param string $message
   *
   * @return object
   *   The unserialized object.
   */
  protected function assertSerialization($object, $message = '') {
    $unserialized = unserialize(serialize($object));
    $this->assertInstanceOf(get_class($object), $unserialized, $message);
    return $unserialized;
  }

  /**
   * Create a basic page.
   *
   * @return \Drupal\page_manager\Entity\Page
   */
  protected function createPage() {
    return Page::create([
      'id' => $this->randomMachineName(),
      'label' => $this->randomString(),
      'description' => $this->randomString(),
      'path' => 'admin/foo',
      'use_admin_theme' => TRUE,
    ]);
  }

  /**
   * Create a basic page variant.
   *
   * @return \Drupal\page_manager\Entity\PageVariant
   */
  protected function createPageVariant() {
    return PageVariant::create([
      'id' => $this->randomMachineName(),
      'label' => $this->randomString(),
      'weight' => 0,
      'variant' => 'block_display',
    ]);
  }

  /**
   * Test serialization of a page.
   *
   * @covers \Drupal\page_manager\Entity\Page::__sleep
   */
  public function testPage() {
    $page = $this->createPage();

    // Test that a very simple page successfully serializes.
    /* @var \Drupal\page_manager\Entity\Page $unserialized */
    $unserialized = $this->assertSerialization($page);
    $this->assertEquals($page->id(), $unserialized->id());
    $this->assertEquals($page->label(), $unserialized->label());
    $this->assertEquals($page->getDescription(), $unserialized->getDescription());
    $this->assertEquals($page->getPath(), $unserialized->getPath());
    $this->assertEquals($page->usesAdminTheme(), $unserialized->usesAdminTheme());

    // Test adding parameters.
    $page->set('path', 'admin/foo/{id}');
    $page->setParameter('id', 'integer', 'ID');
    $unserialized = $this->assertSerialization($page);
    $this->assertEquals($page->getPath(), $unserialized->getPath());
    $this->assertEquals($page->getParameters(), $unserialized->getParameters());

    // Test adding access conditions.
    $condition = [
      'id' => 'request_path',
      'pages' => '/admin/foo/*',
      'negate' => FALSE,
      'context_mapping' => [],
    ];
    $page->addAccessCondition($condition);
    $unserialized = $this->assertSerialization($page);
    $this->assertNull($unserialized->get('accessConditionCollection'));
    $this->assertEquals($page->getAccessConditions()->getConfiguration(), $unserialized->getAccessConditions()->getConfiguration());

    // Test adding context.
    $context = new Context(new ContextDefinition('integer', 'ID'), 1);
    $page->addContext('id', $context);
    $unserialized = $this->assertSerialization($page);
    $this->assertEquals([], $unserialized->get('contexts'));

    // Test adding a very basic variant.
    $page_variant = $this->createPageVariant();
    $page->addVariant($page_variant);
    $unserialized = $this->assertSerialization($page);
    $this->assertInstanceOf(PageVariant::class, $unserialized->getVariant($page_variant->id()));
    $this->assertEquals($page_variant->id(), $unserialized->getVariant($page_variant->id())->id());
  }

  /**
   * Test serialization of a variant.
   *
   * @covers \Drupal\page_manager\Entity\PageVariant::__sleep
   */
  public function testPageVariant() {
    $page_variant = $this->createPageVariant();

    // Test that a very simple page variant successfully serializes.
    /* @var \Drupal\page_manager\Entity\PageVariant $unserialized */
    $unserialized = $this->assertSerialization($page_variant);
    $this->assertEquals($page_variant->id(), $unserialized->id());
    $this->assertEquals($page_variant->label(), $unserialized->label());
    $this->assertEquals($page_variant->getWeight(), $unserialized->getWeight());
    $this->assertEquals($page_variant->getVariantPluginId(), $unserialized->getVariantPluginId());

    // Test setting the page.
    $page = $this->createPage();
    $page_variant->setPageEntity($page);
    $unserialized = $this->assertSerialization($page_variant);
    $this->assertInstanceOf(Page::class, $unserialized->getPage());
    $this->assertEquals($page->id(), $unserialized->getPage()->id());

    // Test adding static context.
    $page_variant->setStaticContext('test', [
      'label' => 'Test',
      'type' => 'integer',
      'value' => 1,
    ]);
    $unserialized = $this->assertSerialization($page_variant);
    $this->assertEquals($page_variant->getStaticContexts(), $unserialized->getStaticContexts());

    // Add context to the page directly to avoid the
    // \Drupal\page_manager\Event\PageManagerEvents::PAGE_CONTEXT event which
    // relies on the router.
    $context = new Context(new ContextDefinition('integer', 'ID'), 1);
    $page->addContext('id', $context);

    // Test initializing context.
    $page_variant->getContexts();
    $unserialized = $this->assertSerialization($page_variant);
    $this->assertNull($unserialized->get('contexts'));

    // Test adding selection criteria.
    $condition = [
      'id' => 'request_path',
      'pages' => '/admin/foo/*',
      'negate' => FALSE,
      'context_mapping' => [],
    ];
    $page_variant->addSelectionCondition($condition);
    $unserialized = $this->assertSerialization($page_variant);
    $this->assertNull($unserialized->get('selectionConditionCollection'));
    $this->assertEquals($page_variant->getSelectionConditions()->getConfiguration(), $unserialized->getSelectionConditions()->getConfiguration());

    // Initialize the variant plugin.
    $page_variant->getVariantPlugin();
    $unserialized = $this->assertSerialization($page_variant);
    $this->assertNull($unserialized->get('variantPluginCollection'));

    // Test adding variant settings.
    $page_variant->getVariantPlugin()->setConfiguration([
      'page_title' => $this->randomString(),
      'blocks' => [],
    ]);
    $unserialized = $this->assertSerialization($page_variant);
    $this->assertEquals($page_variant->getVariantPlugin()->getConfiguration(), $unserialized->getVariantPlugin()->getConfiguration());
  }

  /**
   * Test serialization of a block_display variant plugin.
   */
  public function testPageBlockVariantPlugin() {
    $configuration = [
      'page_title' => 'Test variant',
    ];
    /* @var \Drupal\page_manager\Plugin\DisplayVariant\PageBlockDisplayVariant $variant_plugin */
    $variant_plugin = $this->container
      ->get('plugin.manager.display_variant')
      ->createInstance('block_display', $configuration);
    $this->assertInstanceOf(PageBlockDisplayVariant::class, $variant_plugin);

    // Test that a very simple variant successfully serializes.
    /* @var \Drupal\page_manager\Plugin\DisplayVariant\PageBlockDisplayVariant $unserialized */
    $unserialized = $this->assertSerialization($variant_plugin);
    $this->assertEquals($variant_plugin->getConfiguration(), $unserialized->getConfiguration());

    // Set some context.
    $context = new Context(new ContextDefinition('integer', 'ID'), 1);
    $variant_plugin->setContexts(['id' => $context]);
    $unserialized = $this->assertSerialization($variant_plugin);
    $this->assertEquals([], $unserialized->getContexts());
  }

  /**
   * Test serialization of a block_display variant plugin.
   */
  public function testHttpStatusCodeVariantPlugin() {
    $configuration = [
      'status_code' => '404',
    ];
    /* @var \Drupal\page_manager\Plugin\DisplayVariant\HttpStatusCodeDisplayVariant $variant_plugin */
    $variant_plugin = $this->container
      ->get('plugin.manager.display_variant')
      ->createInstance('http_status_code', $configuration);
    $this->assertInstanceOf(HttpStatusCodeDisplayVariant::class, $variant_plugin);

    // Test that a very simple variant successfully serializes.
    /* @var \Drupal\page_manager\Plugin\DisplayVariant\PageBlockDisplayVariant $unserialized */
    $unserialized = $this->assertSerialization($variant_plugin);
    $this->assertEquals($variant_plugin->getConfiguration(), $unserialized->getConfiguration());
  }

}
