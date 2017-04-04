<?php

namespace Drupal\Tests\panelizer\Unit;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityViewBuilderInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Plugin\Context\ContextInterface;
use Drupal\panelizer\PanelizerEntityViewBuilder;
use Drupal\panelizer\PanelizerInterface;
use Drupal\panelizer\Plugin\PanelizerEntityInterface;
use Drupal\panelizer\Plugin\PanelizerEntityManagerInterface;
use Drupal\panels\PanelsDisplayManagerInterface;
use Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the PanelizerEntityViewBuilder.
 *
 * @coversDefaultClass \Drupal\panelizer\PanelizerEntityViewBuilder
 *
 * @group panelizer
 */
class PanelizerEntityViewBuilderTest extends UnitTestCase {

  /**
   * Information about the entity type.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $entityType;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $entityTypeManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $moduleHandler;

  /**
   * The panelizer service.
   *
   * @var \Drupal\panelizer\PanelizerInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $panelizer;

  /**
   * The Panelizer entity manager.
   *
   * @var \Drupal\panelizer\Plugin\PanelizerEntityManager|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $panelizerManager;

  /**
   * The Panels display manager.
   *
   * @var \Drupal\Panels\PanelsDisplayManagerInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $panelsManager;

  /**
   * The Panelizer entity view builder.
   *
   * @var \Drupal\panelizer\PanelizerEntityViewBuilder|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityViewBuilder;

  /**
   * The fallback entity view module.
   *
   * @var \Drupal\Core\Entity\EntityViewBuilderInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $fallbackViewBuilder;

  /**
   * The Panelizer entity plugin for this entity type.
   *
   * @var \Drupal\panelizer\Plugin\PanelizerEntityInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $panelizerPlugin;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->entityType = $this->prophesize(EntityTypeInterface::class);
    $this->entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $this->moduleHandler = $this->prophesize(ModuleHandlerInterface::class);
    $this->panelizer = $this->prophesize(PanelizerInterface::class);
    $this->panelizerManager = $this->prophesize(PanelizerEntityManagerInterface::class);
    $this->panelsManager = $this->prophesize(PanelsDisplayManagerInterface::class);

    $this->entityType->id()
      ->willReturn('entity_type_id');

    $this->entityViewBuilder = $this->getMockBuilder(PanelizerEntityViewBuilder::class)
      ->setConstructorArgs([
        $this->entityType->reveal(),
        $this->entityTypeManager->reveal(),
        $this->moduleHandler->reveal(),
        $this->panelizer->reveal(),
        $this->panelizerManager->reveal(),
        $this->panelsManager->reveal()
      ])
      ->setMethods(['getFallbackViewBuilder', 'getPanelizerPlugin', 'collectRenderDisplays', 'getEntityContext'])
      ->getMock();

    $this->fallbackViewBuilder = $this->prophesize(EntityViewBuilderInterface::class);
    $this->panelizerPlugin = $this->prophesize(PanelizerEntityInterface::class);

    $this->entityViewBuilder->method('getFallbackViewBuilder')
      ->willReturn($this->fallbackViewBuilder->reveal());
    $this->entityViewBuilder->method('getPanelizerPlugin')
      ->willReturn($this->panelizerPlugin->reveal());
  }

  /**
   * Tests buildComponents().
   *
   * @covers ::buildComponents
   */
  public function testBuildComponents() {
    $build = ['random_value' => 123];

    $entity1 = $this->prophesize(FieldableEntityInterface::class);
    $entity1->bundle()->willReturn('abc');

    $display1 = $this->prophesize(EntityViewDisplayInterface::class);
    $display1->getThirdPartySetting('panelizer', 'enable', FALSE)
      ->willReturn(TRUE);

    $entity2 = $this->prophesize(FieldableEntityInterface::class);
    $entity2->bundle()->willReturn('xyz');

    $display2 = $this->prophesize(EntityViewDisplayInterface::class);
    $display2->getThirdPartySetting('panelizer', 'enable', FALSE)
      ->willReturn(FALSE);

    $displays = [
      'abc' => $display1->reveal(),
      'xyz' => $display2->reveal(),
    ];

    $this->fallbackViewBuilder->buildComponents($build, [234 => $entity2->reveal()], $displays, 'full')
      ->shouldBeCalled();

    $this->moduleHandler->invokeAll('entity_prepare_view', [
      'entity_type_id',
      [123 => $entity1->reveal()],
      $displays,
      'full'
    ])->shouldBeCalled();

    $this->entityViewBuilder->buildComponents(
      $build,
      [
        123 => $entity1->reveal(),
        234 => $entity2->reveal()
      ],
      $displays,
      'full'
    );
  }

  /**
   * Setups up the mock objects for testing view() and viewMultiple().
   *
   * @return array
   *   An associative array with the following keys:
   *   - entities: Associative array of the mocked entity objects, keyed by the
   *     id.
   *   - expected: Associative array of the built render arrays keyed by the
   *     entity id.
   */
  protected function setupView() {
    $entity1 = $this->prophesize(FieldableEntityInterface::class);
    $entity1->bundle()->willReturn('abc');
    $entity1->getEntityTypeId()->willReturn('entity_type_id');
    $entity1->id()->willReturn(123);
    $entity1->getCacheContexts()->willReturn(['context']);
    $entity1->getCacheTags()->willReturn(['tag']);
    $entity1->getCacheMaxAge()->willReturn(123);

    $display1 = $this->prophesize(EntityViewDisplayInterface::class);
    $display1->getThirdPartySetting('panelizer', 'enable', FALSE)
      ->willReturn(TRUE);

    $entity2 = $this->prophesize(FieldableEntityInterface::class);
    $entity2->bundle()->willReturn('xyz');
    $entity2->getEntityTypeId()->willReturn('entity_type_id');

    $display2 = $this->prophesize(EntityViewDisplayInterface::class);
    $display2->getThirdPartySetting('panelizer', 'enable', FALSE)
      ->willReturn(FALSE);

    $this->entityViewBuilder
      ->method('collectRenderDisplays')
      ->willReturn([
        'abc' => $display1->reveal(),
        'xyz' => $display2->reveal()
      ]);

    $entity_context = $this->prophesize(ContextInterface::class);
    $this->entityViewBuilder->method('getEntityContext')
      ->willReturn($entity_context->reveal());

    $panels_display = $this->prophesize(PanelsDisplayVariant::class);
    $other_context = $this->prophesize(ContextInterface::class);
    $panels_display->getContexts()
      ->willReturn(['other' => $other_context->reveal()]);
    $panels_display->setContexts([
      'other' => $other_context->reveal(),
      '@panelizer.entity_context:entity' => $entity_context->reveal(),
    ])->shouldBeCalled();
    $panels_display->build()->willReturn(['#markup' => 'Panelized']);

    $this->panelizer
      ->getPanelizerSettings('entity_type_id', 'abc', 'full', $display1->reveal())
      ->willReturn([
        'default' => 'default',
      ]);

    $this->panelizer
      ->getDisplayStaticContexts('default', 'entity_type_id', 'abc', 'full', $display1->reveal())
      ->willReturn([
        'other' => $other_context->reveal(),
        '@panelizer.entity_context:entity' => $entity_context->reveal(),
      ]);

    $this->panelizer->getPanelsDisplay($entity1->reveal(), 'full', $display1->reveal())
      ->willReturn($panels_display->reveal());

    $panels_display->getCacheContexts()->willReturn([]);
    $panels_display->getCacheTags()->willReturn([]);
    $panels_display->getCacheMaxAge()->willReturn(-1);

    return [
      'entities' => [
        123 => $entity1->reveal(),
        234 => $entity2->reveal(),
      ],
      'expected' => [
        123 => [
          '#theme' => [
            'panelizer_view_mode__entity_type_id__123',
            'panelizer_view_mode__entity_type_id__abc',
            'panelizer_view_mode__entity_type_id',
            'panelizer_view_mode',
          ],
          '#panelizer_plugin' => $this->panelizerPlugin->reveal(),
          '#panels_display' => $panels_display->reveal(),
          '#entity' => $entity1->reveal(),
          '#view_mode' => 'full',
          '#langcode' => 'pl',
          'content' => [
            '#markup' => 'Panelized',
          ],
          '#cache' => [
            'tags' => ['tag'],
            'contexts' => ['context'],
            'max-age' => 123,
          ],
        ],
        234 => [
          '#markup' => 'Fallback',
        ],
      ]
    ];
  }

  /**
   * Tests view().
   *
   * @covers ::view
   */
  public function testView() {
    $data = $this->setupView();
    $entities = $data['entities'];
    $expected = $data['expected'];

    $this->fallbackViewBuilder->view($entities[234], 'full', 'pl')
      ->willReturn(['#markup' => 'Fallback']);

    $this->assertEquals($expected[123], $this->entityViewBuilder->view($entities[123], 'full', 'pl'));
    $this->assertEquals($expected[234], $this->entityViewBuilder->view($entities[234], 'full', 'pl'));
  }

  /**
   * Tests viewMultiple().
   *
   * @covers ::viewMultiple
   */
  public function testViewMultiple() {
    $data = $this->setupView();
    $entities = $data['entities'];
    $expected = $data['expected'];

    $this->fallbackViewBuilder->viewMultiple([234 => $entities[234]], 'full', 'pl')
      ->willReturn([234 => ['#markup' => 'Fallback']]);

    $this->assertEquals($expected, $this->entityViewBuilder->viewMultiple($entities, 'full', 'pl'));
  }

  /**
   * Tests resetCache().
   *
   * @covers ::resetCache
   */
  public function testResetCache() {
    $entities = [
      $this->prophesize(EntityInterface::class)->reveal(),
      $this->prophesize(EntityInterface::class)->reveal(),
    ];
    $this->fallbackViewBuilder->resetCache($entities)->shouldBeCalled();
    $this->entityViewBuilder->resetCache($entities);
  }

  /**
   * Tests viewField().
   *
   * @covers ::viewField
   */
  public function testViewField() {
    $items = $this->prophesize(FieldItemListInterface::class)->reveal();
    $display_options = ['abc' => 123];
    $this->fallbackViewBuilder->viewField($items, $display_options)
      ->willReturn(['#markup' => 'field']);
    $this->assertEquals(['#markup' => 'field'], $this->entityViewBuilder->viewField($items, $display_options));
  }

  /**
   * Tests viewFieldItem().
   *
   * @covers ::viewFieldItem
   */
  public function testViewFieldItem() {
    $item = $this->prophesize(FieldItemInterface::class)->reveal();
    $display = ['abc' => 123];
    $this->fallbackViewBuilder->viewFieldItem($item, $display)
      ->willReturn(['#markup' => 'item']);
    $this->assertEquals(['#markup' => 'item'], $this->entityViewBuilder->viewFieldItem($item, $display));
  }

  /**
   * Tests getCacheTags().
   *
   * @covers ::getCacheTags
   */
  public function testGetCacheTags() {
    $this->fallbackViewBuilder->getCacheTags()
      ->willReturn(['tag']);
    $this->assertEquals(['tag'], $this->entityViewBuilder->getCacheTags());
  }

}
