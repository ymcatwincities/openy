<?php

namespace Drupal\Tests\page_manager\Kernel;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\page_manager\Entity\Page;
use Drupal\page_manager\Entity\PageVariant;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Integration test for Page Manager routing.
 *
 * @group PageManager
 */
class PageManagerRoutingTest extends EntityKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['page_manager', 'page_manager_routing_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->container->get('current_user')->setAccount($this->createUser([], ['view test entity']));
    EntityTest::create()->save();

    Page::create([
      'id' => 'entity_test_view',
      'path' => '/entity_test/{entity_test}',
    ])->save();
    PageVariant::create([
      'id' => 'entity_test_view_variant',
      'variant' => 'simple_page',
      'page' => 'entity_test_view',
    ])->save();

    Page::create([
      'id' => 'custom_entity_test_view',
      'path' => '/custom/entity_test/{entity_test}',
      'parameters' => [
        'entity_test' => [
          'type' => 'entity:entity_test',
        ],
      ],
    ])->save();
    $variant = PageVariant::create([
      'id' => 'custom_entity_test_view_variant',
      'variant' => 'simple_page',
      'page' => 'custom_entity_test_view',
    ]);
    $variant->addSelectionCondition([
      'id' => 'page_manager_routing_test__entity_test',
    ]);
    $variant->getPluginCollections();
    $variant->save();

    Page::create([
      'id' => 'entity_test_edit',
      'path' => '/entity_test/manage/{entity_test}/edit',
    ])->save();
    PageVariant::create([
      'id' => 'entity_test_edit_variant',
      'variant' => 'simple_page',
      'page' => 'entity_test_edit',
      // Add a selection condition that will never pass.
      'selection_criteria' => [
        'request_path' => [
          'id' => 'request_path',
          'pages' => 'invalid',
        ],
      ],
    ])->save();

    Page::create([
      'id' => 'entity_test_delete',
      'path' => '/entity_test/delete/entity_test/{entity_test}',
      // Add an access condition that will never pass.
      'access_conditions' => [
        'request_path' => [
          'id' => 'request_path',
          'pages' => 'invalid',
        ],
      ],
    ])->save();
    PageVariant::create([
      'id' => 'entity_test_delete_variant',
      'variant' => 'simple_page',
      'page' => 'entity_test_delete',
    ])->save();
  }

  /**
   * @covers \Drupal\page_manager\Routing\VariantRouteFilter
   *
   * @dataProvider providerTestRouteFilter
   */
  public function testRouteFilter($path, $expected) {
    $request = Request::create($path);
    try {
      $parameters = $this->container->get('router')->matchRequest($request);
    }
    catch (\Exception $e) {
      $parameters = [];
    }

    if ($expected) {
      $this->assertArrayHasKey(RouteObjectInterface::ROUTE_NAME, $parameters);
      $this->assertSame($expected, $parameters[RouteObjectInterface::ROUTE_NAME]);
    }
    else {
      $this->assertEmpty($parameters);
    }
  }

  public function providerTestRouteFilter() {
    $data = [];
    $data['custom'] = [
      '/custom/entity_test/1',
      'page_manager.page_view_custom_entity_test_view_custom_entity_test_view_variant',
    ];
    $data['no_format'] = [
      '/entity_test/1',
      'entity.entity_test.canonical',
    ];
    $data['format_added_after'] = [
      '/entity_test/1?_format=json',
      'entity.entity_test.canonical.json',
    ];
    $data['format_added_before'] = [
      '/entity_test/1?_format=xml',
      'entity.entity_test.canonical.xml',
    ];
    $data['same_pattern_no_match'] = [
      '/entity_test/add',
      'entity.entity_test.add_form',
    ];
    $data['failed_selection'] = [
      '/entity_test/manage/1/edit',
      'entity.entity_test.edit_form',
    ];
    $data['access_denied'] = [
      '/entity_test/delete/entity_test/1',
      NULL,
    ];
    return $data;
  }

}
