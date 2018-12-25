<?php

/**
 * @file
 * Contains \Drupal\Tests\page_manager\Unit\PageBlockDisplayVariantTest.
 */

namespace Drupal\Tests\page_manager\Unit;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockManager;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\Condition\ConditionManager;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\Context\ContextHandlerInterface;
use Drupal\Core\Plugin\ContextAwarePluginInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Utility\Token;
use Drupal\ctools\Plugin\BlockPluginCollection;
use Drupal\page_manager\PageInterface;
use Drupal\page_manager\Plugin\DisplayVariant\PageBlockDisplayVariant;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Tests the block variant plugin.
 *
 * @coversDefaultClass \Drupal\page_manager\Plugin\DisplayVariant\PageBlockDisplayVariant
 *
 * @group PageManager
 */
class PageBlockDisplayVariantTest extends UnitTestCase {

  /**
   * Tests the build() method when a block is empty.
   *
   * @covers ::build
   * @covers ::buildRegions
   * @covers ::buildBlock
   */
  public function testBuildEmptyBlock() {
    $account = $this->prophesize(AccountInterface::class);
    $block1 = $this->prophesize(BlockPluginInterface::class);
    $block1->access($account)->willReturn(TRUE);

    // Building a block with empty content.
    $block1->build()->willReturn(['#cache' => [ 'tags' => [ 0 => 'tag_to_be_merged']]]);

    $context_handler = $this->prophesize(ContextHandlerInterface::class);
    $uuid_generator = $this->prophesize(UuidInterface::class);
    $token = $this->prophesize(Token::class);
    $block_manager = $this->prophesize(BlockManager::class);
    $condition_manager = $this->prophesize(ConditionManager::class);
    $module_handler = $this->prophesize(ModuleHandlerInterface::class);

    $variant_plugin = new PageBlockDisplayVariant([], '', [], $context_handler->reveal(), $account->reveal(), $uuid_generator->reveal(), $token->reveal(), $block_manager->reveal(), $condition_manager->reveal(), $module_handler->reveal());

    // Empty block.
    $expected_build = [
      '#markup' => '',
      '#cache' => [
        'tags' => [
          'block_plugin:block_plugin_id',
          'page:page_id',
          'tag_to_be_merged',
        ],
        'contexts' => [],
        'max-age' => -1,
      ],
    ];

    $build = [
      '#block_plugin' => $block1->reveal(),
      '#cache' => [
        'tags' => [
          'page:page_id',
          'block_plugin:block_plugin_id',
        ],
      ],
    ];

    $build = $variant_plugin->buildBlock($build);

    // Assert that cacheability metadata is merged.
    $this->assertSame($expected_build, $build);
  }

  /**
   * Tests the build() method when blocks can be cached.
   *
   * @covers ::build
   * @covers ::buildRegions
   * @covers ::buildBlock
   */
  public function testBuild() {
    $container = new ContainerBuilder();
    $cache_contexts = $this->prophesize(CacheContextsManager::class);
    $container->set('cache_contexts_manager', $cache_contexts->reveal());
    \Drupal::setContainer($container);

    $account = $this->prophesize(AccountInterface::class);

    // Define one block that allows access, access varies by permissions.
    $cache_contexts->assertValidTokens(['user.permissions'])->willReturn(TRUE);
    $block1 = $this->prophesize(BlockPluginInterface::class);
    $block1->access($account, TRUE)->willReturn(AccessResult::allowed()->cachePerPermissions());
    $block1->getConfiguration()->willReturn(['label' => 'Block label']);
    $block1->getPluginId()->willReturn('block_plugin_id');
    $block1->getBaseId()->willReturn('block_base_plugin_id');
    $block1->getDerivativeId()->willReturn('block_derivative_plugin_id');
    $block1->getCacheTags()->willReturn(['block_plugin1:block_plugin_id']);
    $block1->getCacheMaxAge()->willReturn(3600);
    $block1->getCacheContexts()->willReturn(['url']);

    // Define another block that doesn't allow access, varies by user.
    $cache_contexts->assertValidTokens(['user'])->willReturn(TRUE);
    $block2 = $this->prophesize()->willImplement(ContextAwarePluginInterface::class)->willImplement(BlockPluginInterface::class);
    $block2->access($account, TRUE)->willReturn(AccessResult::forbidden()->cachePerUser());
    $block2->getConfiguration()->willReturn([]);
    $block2->getPluginId()->willReturn('block_plugin_id');
    $block2->getBaseId()->willReturn('block_base_plugin_id');
    $block2->getDerivativeId()->willReturn('block_derivative_plugin_id');
    // The block is not shown, so cacheability metadata is not collected.
    $block2->getCacheContexts()->shouldNotBeCalled();
    $block2->getCacheMaxAge()->shouldNotBeCalled();
    $block2->getCacheTags()->shouldNotBeCalled();
    $blocks = [
      'top' => [
        'block1' => $block1->reveal(),
        'block2' => $block2->reveal(),
      ],
    ];
    $block_collection = $this->getMockBuilder(BlockPluginCollection::class)
      ->disableOriginalConstructor()
      ->getMock();
    $block_collection->expects($this->once())
      ->method('getAllByRegion')
      ->willReturn($blocks);

    $context_handler = $this->prophesize(ContextHandlerInterface::class);
    $context_handler->applyContextMapping($block2->reveal(), [])->shouldBeCalledTimes(1);

    $module_handler = $this->prophesize(ModuleHandlerInterface::class);
    $module_handler->alter();
    $uuid_generator = $this->prophesize(UuidInterface::class);
    $page_title = 'Page title';
    $token = $this->getMockBuilder(Token::class)
      ->disableOriginalConstructor()
      ->getMock();
    $block_manager = $this->prophesize(BlockManager::class);
    $condition_manager = $this->prophesize(ConditionManager::class);
    $variant_plugin = $this->getMockBuilder(PageBlockDisplayVariant::class)
      ->setConstructorArgs([['page_title' => $page_title, 'uuid' => 'UUID'], 'test', [], $context_handler->reveal(), $account->reveal(), $uuid_generator->reveal(), $token, $block_manager->reveal(), $condition_manager->reveal(), $module_handler->reveal()])
      ->setMethods(['renderPageTitle'])
      ->getMock();

    $property = new \ReflectionProperty($variant_plugin, 'blockPluginCollection');
    $property->setAccessible(TRUE);
    $property->setValue($variant_plugin, $block_collection);

    $page = $this->prophesize(PageInterface::class);
    $page->id()->willReturn('page_id');

    $variant_plugin->expects($this->once())
      ->method('renderPageTitle')
      ->with($page_title)
      ->willReturn($page_title);

    $expected_cache_block1 = [
      'keys' => ['page_manager_block_display', 'UUID', 'block', 'block1'],
      'tags' => ['block_plugin1:block_plugin_id'],
      'contexts' => ['url'],
      'max-age' => 3600,
    ];
    $cache_contexts->assertValidTokens(['user.permissions', 'url'])->willReturn(TRUE);

    // The page cacheability metadata contains the access cacheability metadata
    // of accessible and non-accessible blocks. Additionally, the cacheability
    // metadata of accessible blocks is merged to avoid cache redirects when
    // possible.
    $expected_cache_page = [
      'keys' => ['page_manager_block_display', 'UUID'],
      'contexts' => ['url', 'user', 'user.permissions'],
      'tags' => ['block_plugin1:block_plugin_id'],
      'max-age' => 3600,
    ];
    $cache_contexts->assertValidTokens(['url', 'user.permissions', 'user'])->willReturn(TRUE);

    // Build the variant and ensure that pre_render is set only for the first
    // block.
    $build = $variant_plugin->build();
    $build = $variant_plugin->buildRegions($build);
    $this->assertSame([$variant_plugin, 'buildBlock'], $build['top']['block1']['#pre_render'][0]);
    $this->assertTrue(empty($build['top']['block2']));
    $this->assertSame($expected_cache_block1, $build['top']['block1']['#cache']);
    $this->assertSame($expected_cache_page, $build['#cache']);

    // Ensure that building the block returns the correct markup.
    $block1->build()->willReturn([
      '#markup' => 'block1_build_value',
    ]);
    $block1_build = $variant_plugin->buildBlock($build['top']['block1']);
    $this->assertSame(['#markup' => 'block1_build_value'], $block1_build['content']);
  }

  /**
   * Tests the submitConfigurationForm() method.
   *
   * @covers ::submitConfigurationForm
   */
  public function testSubmitConfigurationForm() {
    $account = $this->prophesize(AccountInterface::class);
    $context_handler = $this->prophesize(ContextHandlerInterface::class);
    $uuid_generator = $this->prophesize(UuidInterface::class);
    $token = $this->prophesize(Token::class);
    $block_manager = $this->prophesize(BlockManager::class);
    $condition_manager = $this->prophesize(ConditionManager::class);
    $module_handler = $this->prophesize(ModuleHandlerInterface::class);

    $variant_plugin = new PageBlockDisplayVariant([], '', [], $context_handler->reveal(), $account->reveal(), $uuid_generator->reveal(), $token->reveal(), $block_manager->reveal(), $condition_manager->reveal(), $module_handler->reveal());

    $values = ['page_title' => "Go hang a salami, I'm a lasagna hog!"];

    $form = [];
    $form_state = (new FormState())->setValues($values);
    $variant_plugin->submitConfigurationForm($form, $form_state);

    $property = new \ReflectionProperty($variant_plugin, 'configuration');
    $property->setAccessible(TRUE);
    $this->assertSame($values['page_title'], $property->getValue($variant_plugin)['page_title']);
  }

}
