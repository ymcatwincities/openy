<?php

namespace Drupal\panels_ipe\Controller;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Plugin\Context\ContextHandlerInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\RendererInterface;
use Drupal\layout_plugin\Plugin\Layout\LayoutPluginManagerInterface;
use Drupal\panels\Storage\PanelsStorageManagerInterface;
use Drupal\panels_ipe\Helpers\RemoveBlockRequestHandler;
use Drupal\panels_ipe\Helpers\UpdateLayoutRequestHandler;
use Drupal\panels_ipe\PanelsIPEBlockRendererTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\user\SharedTempStoreFactory;

/**
 * Contains all JSON endpoints required for Panels IPE + Page Manager.
 */
class PanelsIPEPageController extends ControllerBase {

  use PanelsIPEBlockRendererTrait;

  /**
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $blockManager;

  /**
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * @var \Drupal\layout_plugin\Plugin\Layout\LayoutPluginManagerInterface
   */
  protected $layoutPluginManager;

  /**
   * The Panels storage manager.
   *
   * @var \Drupal\panels\Storage\PanelsStorageManagerInterface
   */
  protected $panelsStorage;

  /**
   * @var \Drupal\user\SharedTempStore
   */
  protected $tempStore;

  /**
   * @var \Drupal\panels_ipe\Helpers\UpdateLayoutRequestHandler
   */
  private $updateLayoutRequestHandler;

  /**
   * @var \Drupal\panels_ipe\Helpers\RemoveBlockRequestHandler
   */
  private $removeBlockRequestHandler;

  /**
   * Constructs a new PanelsIPEController.
   *
   * @param \Drupal\Core\Block\BlockManagerInterface $block_manager
   * @param \Drupal\Core\Render\RendererInterface $renderer
   * @param \Drupal\layout_plugin\Plugin\Layout\LayoutPluginManagerInterface $layout_plugin_manager
   * @param \Drupal\panels\Storage\PanelsStorageManagerInterface $panels_storage_manager
   * @param \Drupal\user\SharedTempStoreFactory $temp_store_factory
   * @param \Drupal\Core\Plugin\Context\ContextHandlerInterface $context_handler
   */
  public function __construct(BlockManagerInterface $block_manager, RendererInterface $renderer, LayoutPluginManagerInterface $layout_plugin_manager, PanelsStorageManagerInterface $panels_storage_manager, SharedTempStoreFactory $temp_store_factory, ContextHandlerInterface $context_handler) {
    $this->blockManager = $block_manager;
    $this->renderer = $renderer;
    $this->layoutPluginManager = $layout_plugin_manager;
    $this->panelsStorage = $panels_storage_manager;
    $this->tempStore = $temp_store_factory->get('panels_ipe');
    $this->contextHandler = $context_handler;
    $this->updateLayoutRequestHandler = new UpdateLayoutRequestHandler($this->moduleHandler(), $this->panelsStorage, $this->tempStore);
    $this->removeBlockRequestHandler = new RemoveBlockRequestHandler($this->moduleHandler(), $this->panelsStorage, $this->tempStore);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.block'),
      $container->get('renderer'),
      $container->get('plugin.manager.layout_plugin'),
      $container->get('panels.storage_manager'),
      $container->get('user.shared_tempstore'),
      $container->get('context.handler')
    );
  }

  /**
   * Takes the current Page Variant and returns a possibly modified Page Variant
   * based on what's in TempStore for this user.
   *
   * @param string $panels_storage_type
   *   The Panels storage plugin which holds the Panels display.
   * @param string $panels_storage_id
   *   The id within the Panels storage plugin for this Panels display.
   *
   * @return \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant|NULL
   */
  protected function loadPanelsDisplay($panels_storage_type, $panels_storage_id) {
    /** @var \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant $panels_display */
    $panels_display = $this->panelsStorage->load($panels_storage_type, $panels_storage_id);

    // If a temporary configuration for this variant exists, use it.
    if ($variant_config = $this->tempStore->get($panels_display->id())) {
      $panels_display->setConfiguration($variant_config);
    }

    return $panels_display;
  }

  /**
   * Removes any temporary changes to the variant.
   *
   * @param string $panels_storage_type
   *   The id of the storage plugin.
   * @param string $panels_storage_id
   *   The id within the storage plugin for the requested Panels display.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *
   * @throws \Drupal\user\TempStoreException
   */
  public function cancel($panels_storage_type, $panels_storage_id) {
    $panels_display = $this->loadPanelsDisplay($panels_storage_type, $panels_storage_id);

    // If a temporary configuration for this variant exists, use it.
    $temp_store_key = $panels_display->id();
    if ($variant_config = $this->tempStore->get($temp_store_key)) {
      $this->tempStore->delete($temp_store_key);
    }

    // Return an empty JSON response.
    return new JsonResponse();
  }

  /**
   * Gets a list of available Layouts, without wrapping HTML.
   *
   * @param string $panels_storage_type
   *   The id of the storage plugin.
   * @param string $panels_storage_id
   *   The id within the storage plugin for the requested Panels display.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function getLayouts($panels_storage_type, $panels_storage_id) {
    $panels_display = $this->loadPanelsDisplay($panels_storage_type, $panels_storage_id);

    // Get the current layout.
    $current_layout_id = $panels_display->getLayout()->getPluginId();

    // Get a list of all available layouts.
    $layouts = $this->layoutPluginManager->getDefinitions();
    $base_path = base_path();
    $data = [];
    foreach ($layouts as $id => $layout) {
      $icon = !empty($layout['icon']) ? $layout['icon'] : drupal_get_path('module', 'panels') . '/images/no-layout-preview.png';
      $data[] = [
        'id' => $id,
        'label' => $layout['label'],
        'icon' => $base_path . $icon,
        'current' => $id == $current_layout_id,
        'category' => $layout['category']
      ];
    }

    // Return a structured JSON response for our Backbone App.
    return new JsonResponse($data);
  }

  /**
   * Gets a layout configuration form for the requested layout.
   *
   * @param string $panels_storage_type
   *   The id of the storage plugin.
   * @param string $panels_storage_id
   *   The id within the storage plugin for the requested Panels display.
   * @param string $layout_id
   *   The machine name of the requested layout.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function getLayoutForm($panels_storage_type, $panels_storage_id, $layout_id) {
    $panels_display = $this->loadPanelsDisplay($panels_storage_type, $panels_storage_id);

    // Build a Block Plugin configuration form.
    $form = $this->formBuilder()->getForm('Drupal\panels_ipe\Form\PanelsIPELayoutForm', $layout_id, $panels_display);

    // Return the rendered form as a proper Drupal AJAX response.
    $response = new AjaxResponse();
    $command = new AppendCommand('.ipe-layout-form', $form);
    $response->addCommand($command);
    return $response;
  }

  /**
   * Updates (PUT) an existing Layout in this Variant.
   *
   * @param string $panels_storage_type
   *   The id of the storage plugin.
   * @param string $panels_storage_id
   *   The id within the storage plugin for the requested Panels display.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function handleUpdateLayoutRequest($panels_storage_type, $panels_storage_id, Request $request) {
    $panels_display = $this->loadPanelsDisplay($panels_storage_type, $panels_storage_id);
    $this->updateLayoutRequestHandler->handleRequest($panels_display, $request);
    return $this->updateLayoutRequestHandler->getJsonResponse();
  }

  /**
   * Stores changes to the temporary storage.
   *
   * @param string $panels_storage_type
   *   The id of the storage plugin.
   * @param string $panels_storage_id
   *   The id within the storage plugin for the requested Panels display.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function handleUpdateLayoutTempStorageRequest($panels_storage_type, $panels_storage_id, Request $request) {
    $panels_display = $this->loadPanelsDisplay($panels_storage_type, $panels_storage_id);
    $this->updateLayoutRequestHandler->handleRequest($panels_display, $request, TRUE);
    return $this->updateLayoutRequestHandler->getJsonResponse();
  }

  /**
   * Creates (POST) a new Layout for this Variant.
   *
   * @param string $panels_storage_type
   *   The id of the storage plugin.
   * @param string $panels_storage_id
   *   The id within the storage plugin for the requested Panels display.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function handleCreateLayoutRequest($panels_storage_type, $panels_storage_id, Request $request) {
    // For now, creating and updating a layout is the same thing.
    return $this->handleUpdateLayoutRequest($panels_storage_type, $panels_storage_id, $request);
  }

  /**
   * Removes a block from the layout.
   *
   * @param string $panels_storage_type
   *   The id of the storage plugin.
   * @param string $panels_storage_id
   *   The id within the storage plugin for the requested Panels display.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function handleRemoveBlockRequest($panels_storage_type, $panels_storage_id, Request $request) {
    $panels_display = $this->loadPanelsDisplay($panels_storage_type, $panels_storage_id);
    $this->removeBlockRequestHandler->handleRequest($panels_display, $request, TRUE);
    return $this->updateLayoutRequestHandler->getJsonResponse();
  }

  /**
   * Gets a list of Block Plugins from the server.
   *
   * @param string $panels_storage_type
   *   The id of the storage plugin.
   * @param string $panels_storage_id
   *   The id within the storage plugin for the requested Panels display.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function getBlockPlugins($panels_storage_type, $panels_storage_id) {
    $panels_display = $this->loadPanelsDisplay($panels_storage_type, $panels_storage_id);

    // Get block plugin definitions from the server.
    $definitions = $this->blockManager->getDefinitionsForContexts($panels_display->getContexts());

    // Assemble our relevant data.
    $data = [];
    foreach ($definitions as $plugin_id => $definition) {
      // Don't add broken Blocks.
      if ($plugin_id == 'broken') {
        continue;
      }
      $data[] = [
        'plugin_id' => $plugin_id,
        'label' => $definition['admin_label'],
        'category' => $definition['category'],
        'id' => $definition['id'],
        'provider' => $definition['provider'],
      ];
    }

    // Return a structured JSON response for our Backbone App.
    return new JsonResponse($data);
  }

  /**
   * Drupal AJAX compatible route for rendering a given Block Plugin's form.
   *
   * @param string $panels_storage_type
   *   The id of the storage plugin.
   * @param string $panels_storage_id
   *   The id within the storage plugin for the requested Panels display.
   * @param string $plugin_id
   *   The requested Block Plugin ID.
   * @param string $block_uuid
   *   The Block UUID, if this is an existing Block.
   *
   * @return Response
   */
  public function getBlockPluginForm($panels_storage_type, $panels_storage_id, $plugin_id, $block_uuid = NULL) {
    $panels_display = $this->loadPanelsDisplay($panels_storage_type, $panels_storage_id);

    // Get the configuration in the block plugin definition.
    $definitions = $this->blockManager->getDefinitionsForContexts($panels_display->getContexts());

    // Check if the block plugin is defined.
    if (!isset($definitions[$plugin_id])) {
      throw new NotFoundHttpException();
    }

    // Build a Block Plugin configuration form.
    $form = $this->formBuilder()->getForm('Drupal\panels_ipe\Form\PanelsIPEBlockPluginForm', $plugin_id, $panels_display, $block_uuid);

    // Return the rendered form as a proper Drupal AJAX response.
    $response = new AjaxResponse();
    $command = new AppendCommand('.ipe-block-form', $form);
    $response->addCommand($command);
    return $response;
  }

  /**
   * Gets a list of Block Content Types from the server.
   *
   * @param string $panels_storage_type
   *   The id of the storage plugin.
   * @param string $panels_storage_id
   *   The id within the storage plugin for the requested Panels display.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function getBlockContentTypes($panels_storage_type, $panels_storage_id) {
    // Assemble our relevant data.
    $types = $this->entityTypeManager()->getStorage('block_content_type')->loadMultiple();
    $data = [];

    /** @var \Drupal\block_content\BlockContentTypeInterface $definition */
    foreach ($types as $id => $definition) {
      $data[] = [
        'id' => $definition->id(),
        'revision' => $definition->shouldCreateNewRevision(),
        'label' => $definition->label(),
        'description' => $definition->getDescription(),
      ];
    }

    // Return a structured JSON response for our Backbone App.
    return new JsonResponse($data);
  }

  /**
   * Drupal AJAX compatible route for rendering a Block Content Type's form.
   *
   * @param string $panels_storage_type
   *   The id of the storage plugin.
   * @param string $panels_storage_id
   *   The id within the storage plugin for the requested Panels display.
   * @param string $type
   *   The requested Block Type.
   * @param string $block_content_uuid
   *   The Block Content Entity UUID, if this is an existing Block.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   */
  public function getBlockContentForm($panels_storage_type, $panels_storage_id, $type, $block_content_uuid = NULL) {
    $storage = $this->entityTypeManager()->getStorage('block_content');

    // Create or load a new block of the given type.
    if ($block_content_uuid) {
      $block_list = $storage->loadByProperties(['uuid' => $block_content_uuid]);
      $block = array_shift($block_list);

      $operation = 'update';
    }
    else {
      $block = $storage->create([
        'type' => $type
      ]);

      $operation = 'create';
    }

    // Check Block Content entity access for the current operation.
    if (!$block->access($operation)) {
      throw new AccessDeniedHttpException();
    }

    // Grab our Block Content Entity form handler.
    $form = $this->entityFormBuilder()->getForm($block, 'panels_ipe');

    // Return the rendered form as a proper Drupal AJAX response.
    $response = new AjaxResponse();
    $command = new AppendCommand('.ipe-block-form', $form);
    $response->addCommand($command);
    return $response;
  }

  /**
   * Gets a single Block from the current Panels Display. Uses TempStore.
   *
   * @param string $panels_storage_type
   *   The id of the storage plugin.
   * @param string $panels_storage_id
   *   The id within the storage plugin for the requested Panels display.
   * @param string $block_uuid
   *   The Block UUID.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  public function getBlock($panels_storage_type, $panels_storage_id, $block_uuid) {
    $panels_display = $this->loadPanelsDisplay($panels_storage_type, $panels_storage_id);

    /** @var \Drupal\Core\Block\BlockBase $block_instance */
    $block_instance = $panels_display->getBlock($block_uuid);
    $block_config = $block_instance->getConfiguration();

    // Assemble data required for our App.
    $build = $this->buildBlockInstance($block_instance, $panels_display);

    // Bubble Block attributes to fix bugs with the Quickedit and Contextual
    // modules.
    $this->bubbleBlockAttributes($build);

    // Add our data attribute for the Backbone app.
    $build['#attributes']['data-block-id'] = $block_uuid;

    $plugin_definition = $block_instance->getPluginDefinition();

    $block_model = [
      'uuid' => $block_uuid,
      'label' => $block_instance->label(),
      'id' => $block_instance->getPluginId(),
      'region' => $block_config['region'],
      'provider' => $block_config['provider'],
      'plugin_id' => $plugin_definition['id'],
      'html' => $this->renderer->render($build),
    ];

    return new JsonResponse($block_model);
  }

}
