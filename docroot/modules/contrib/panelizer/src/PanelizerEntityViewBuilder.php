<?php

namespace Drupal\panelizer;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\EntityHandlerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityViewBuilderInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\ctools\Context\AutomaticContext;
use Drupal\panelizer\Plugin\PanelizerEntityManagerInterface;
use Drupal\Panels\PanelsDisplayManagerInterface;
use Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Entity view builder for entities that can be panelized.
 */
class PanelizerEntityViewBuilder implements EntityViewBuilderInterface, EntityHandlerInterface {

  /**
   * The type of entities for which this view builder is instantiated.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * Information about the entity type.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $entityType;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The panelizer service.
   *
   * @var \Drupal\panelizer\PanelizerInterface
   */
  protected $panelizer;

  /**
   * The Panelizer entity manager.
   *
   * @var \Drupal\panelizer\Plugin\PanelizerEntityManager
   */
  protected $panelizerManager;

  /**
   * The Panels display manager.
   *
   * @var \Drupal\Panels\PanelsDisplayManagerInterface
   */
  protected $panelsManager;

  /**
   * The Panelizer entity plugin for this entity type.
   *
   * @var \Drupal\panelizer\Plugin\PanelizerEntityInterface
   */
  protected $panelizerPlugin;

  /**
   * Constructs a new EntityViewBuilder.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager service.
   * @param \Drupal\panelizer\PanelizerInterface $panelizer
   *   The Panelizer service.
   * @param \Drupal\panelizer\Plugin\PanelizerEntityManager $panelizer_manager
   *   The Panelizer entity manager.
   * @param \Drupal\Panels\PanelsDisplayManagerInterface $panels_manager
   *   The Panels display manager.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityTypeManagerInterface $entity_type_manager, ModuleHandlerInterface $module_handler, PanelizerInterface $panelizer, PanelizerEntityManagerInterface $panelizer_manager, PanelsDisplayManagerInterface $panels_manager) {
    $this->entityTypeId = $entity_type->id();
    $this->entityType = $entity_type;
    $this->entityTypeManager = $entity_type_manager;
    $this->moduleHandler = $module_handler;
    $this->panelizer = $panelizer;
    $this->panelizerManager = $panelizer_manager;
    $this->panelsManager = $panels_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager'),
      $container->get('module_handler'),
      $container->get('panelizer'),
      $container->get('plugin.manager.panelizer_entity'),
      $container->get('panels.display_manager')
    );
  }

  /**
   * Get the Panelizer entity plugin.
   *
   * @return \Drupal\panelizer\Plugin\PanelizerEntityInterface|FALSE
   */
  protected function getPanelizerPlugin() {
    if (!isset($this->panelizerPlugin)) {
      if (!$this->panelizerManager->hasDefinition($this->entityTypeId)) {
        $this->panelizerPlugin = FALSE;
      }
      else {
        $this->panelizerPlugin = $this->panelizerManager->createInstance($this->entityTypeId, []);
      }
    }

    return $this->panelizerPlugin;
  }

  /**
   * Check if Panelizer should be used for building this display.
   *
   * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display
   *   The display we're building.
   *
   * @return bool
   */
  protected function isPanelizerEnabled(EntityViewDisplayInterface $display) {
    return $display->getThirdPartySetting('panelizer', 'enable', FALSE);
  }

  /**
   * Gets the original view builder for this entity.
   *
   * @return \Drupal\Core\Entity\EntityViewBuilderInterface
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  protected function getFallbackViewBuilder() {
    return $this->entityTypeManager->getHandler($this->entityTypeId, 'fallback_view_builder');
  }

  /**
   * Get the Panels display out of an the entity view display
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface $display
   *   The display.
   * @param $view_mode
   *   The view mode.
   *
   * @return \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant
   *   The Panels display.
   */
  protected function getPanelsDisplay(EntityInterface $entity, EntityViewDisplayInterface $display, $view_mode) {
    return $this->panelizer->getPanelsDisplay($entity, $view_mode, $display);
  }

  /**
   * Returns the display objects used to render a set of entities.
   *
   * Wraps EntityViewDisplay::collectRenderDisplays() so we can mock it in
   * tests.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface[] $entities
   *   The entities being rendered. They should all be of the same entity type.
   * @param string $view_mode
   *   The view mode being rendered.
   *
   * @return \Drupal\Core\Entity\Display\EntityViewDisplayInterface[]
   *   The display objects to use to render the entities, keyed by entity
   *   bundle.
   *
   * @see EntityViewDisplay::collectRenderDisplays()
   */
  protected function collectRenderDisplays($entities, $view_mode) {
    return EntityViewDisplay::collectRenderDisplays($entities, $view_mode);
  }

  /**
   * Returns the entity context.
   *
   * Wraps creating new Context objects to avoid typed data in tests.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return \Drupal\Core\Plugin\Context\Context
   *   The context.
   */
  protected function getEntityContext(EntityInterface $entity) {
    return new AutomaticContext(new ContextDefinition('entity:' . $this->entityTypeId, NULL, TRUE), $entity);
  }

  /*
   * Methods from EntityViewBuilderInterface.
   */

  /**
   * {@inheritdoc}
   */
  public function buildComponents(array &$build, array $entities, array $displays, $view_mode) {
    $fallback_view_builder = $this->getFallbackViewBuilder();

    $panelized_entities = [];
    $fallback_entities = [];
    /**
     * @var string $id
     * @var \Drupal\Core\Entity\EntityInterface $entity
     */
    foreach ($entities as $id => $entity) {
      $display = $displays[$entity->bundle()];
      if ($this->isPanelizerEnabled($display)) {
        $panelized_entities[$id] = $entity;
      }
      else {
        $fallback_entities[$id] = $entity;
      }
    }

    // Handle all the fallback entities first!
    if (!empty($fallback_entities)) {
      $fallback_view_builder->buildComponents($build, $fallback_entities, $displays, $view_mode);
    }

    // Handle the panelized entities.
    if (!empty($panelized_entities)) {
      $this->moduleHandler
        ->invokeAll('entity_prepare_view', [
          $this->entityTypeId,
          $panelized_entities,
          $displays,
          $view_mode
        ]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $entity, $view_mode = 'full', $langcode = NULL) {
    // Trigger hook_panelizer_pre_view_builder_alter().
    $this->moduleHandler->alter('panelizer_pre_view_builder', $view_mode, $entity, $langcode);

    $displays = $this->collectRenderDisplays([$entity], $view_mode);
    $display = $displays[$entity->bundle()];

    if (!$this->isPanelizerEnabled($display)) {
      return $this->getFallbackViewBuilder()->view($entity, $view_mode, $langcode);
    }

    $build = $this->buildMultiplePanelized([$entity->id() => $entity], $displays, $view_mode, $langcode);
    return $build[$entity->id()];
  }

  /**
   * {@inheritdoc}
   */
  public function viewMultiple(array $entities = [], $view_mode = 'full', $langcode = NULL) {
    $displays = $this->collectRenderDisplays($entities, $view_mode);

    $panelized_entities = [];
    $fallback_entities = [];
    foreach ($entities as $id => $entity) {
      $display = $displays[$entity->bundle()];
      if ($this->isPanelizerEnabled($display)) {
        $panelized_entities[$id] = $entity;
      }
      else {
        $fallback_entities[$id] = $entity;
      }
    }

    $result = [];
    if (!empty($fallback_entities)) {
      $result += $this->getFallbackViewBuilder()->viewMultiple($fallback_entities, $view_mode, $langcode);
    }
    if (!empty($panelized_entities)) {
      $result += $this->buildMultiplePanelized($panelized_entities, $displays, $view_mode, $langcode);
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function resetCache(array $entities = NULL) {
    $this->getFallbackViewBuilder()->resetCache($entities);
  }

  /**
   * {@inheritdoc}
   */
  public function viewField(FieldItemListInterface $items, $display_options = []) {
    return $this->getFallbackViewBuilder()->viewfield($items, $display_options);
  }

  /**
   * {@inheritdoc}
   */
  public function viewFieldItem(FieldItemInterface $item, $display = []) {
    return $this->getFallbackViewBuilder()->viewFieldItem($item, $display);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return $this->getFallbackViewBuilder()->getCacheTags();
  }

  /*
   * Methods for actually rendering the Panelized entities.
   */

  /**
   * Build the render array for a list of panelized entities.
   *
   * @param \Drupal\Core\Entity\EntityInterface[] $entities
   * @param \Drupal\Core\Entity\Display\EntityViewDisplayInterface[] $displays
   * @param string $view_mode
   * @param string|NULL $langcode
   *
   * @return array
   */
  protected function buildMultiplePanelized(array $entities, array $displays, $view_mode, $langcode) {
    $build = [];

    foreach ($entities as $id => $entity) {
      $panels_display = $this->panelizer->getPanelsDisplay($entity, $view_mode, $displays[$entity->bundle()]);
      $settings = $this->panelizer->getPanelizerSettings($entity->getEntityTypeId(), $entity->bundle(), $view_mode, $displays[$entity->bundle()]);
      $panels_display->setContexts($this->panelizer->getDisplayStaticContexts($settings['default'], $entity->getEntityTypeId(), $entity->bundle(), $view_mode, $displays[$entity->bundle()]));
      $build[$id] = $this->buildPanelized($entity, $panels_display, $view_mode, $langcode);

      // Allow modules to modify the render array.
      $alter_types = [
        "{$this->entityTypeId}_view",
        'entity_view',
      ];
      $this->moduleHandler->alter($alter_types, $build[$id], $entity, $displays[$entity->bundle()]);
    }

    return $build;
  }

  /**
   * Build the render array for a single panelized entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   * @param \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant $panels_display
   * @param string $view_mode
   * @param string $langcode
   *
   * @return array
   */
  protected function buildPanelized(EntityInterface $entity, PanelsDisplayVariant $panels_display, $view_mode, $langcode) {
    $contexts = $panels_display->getContexts();
    $contexts['@panelizer.entity_context:entity'] = $this->getEntityContext($entity);
    $panels_display->setContexts($contexts);

    $build = [
      '#theme' => [
        'panelizer_view_mode__' . $this->entityTypeId . '__' . $entity->id(),
        'panelizer_view_mode__' . $this->entityTypeId . '__' . $entity->bundle(),
        'panelizer_view_mode__' . $this->entityTypeId,
        'panelizer_view_mode',
      ],
      '#panelizer_plugin' => $this->getPanelizerPlugin(),
      '#panels_display' => $panels_display,
      '#entity' => $entity,
      '#view_mode' => $view_mode,
      '#langcode' => $langcode,
      'content' => $panels_display->build(),
    ];

    if (isset($build['content']['#title'])) {
      $build['#title'] = $build['content']['#title'];
    }

    // @todo: I'm sure more is necessary to get the cache contexts right...
    $entity_metadata = CacheableMetadata::createFromObject($entity);
    CacheableMetadata::createFromObject($panels_display)->merge($entity_metadata)->applyTo($build);

    $this->getPanelizerPlugin()->alterBuild($build, $entity, $panels_display, $view_mode);

    return $build;
  }

}
