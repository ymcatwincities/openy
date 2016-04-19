<?php

/**
 * @file
 * Contains \Drupal\panels_ipe\Controller\PanelsIPEPageController.
 */

namespace Drupal\panels_ipe\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\FormState;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\RendererInterface;
use Drupal\layout_plugin\Plugin\Layout\LayoutPluginManagerInterface;
use Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant;
use Drupal\panels\Storage\PanelsStorageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\user\SharedTempStoreFactory;

/**
 * Contains all JSON endpoints required for Panels IPE + Page Manager.
 */
class PanelsIPEPageController extends ControllerBase {

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
   * Constructs a new PanelsIPEController.
   *
   * @param \Drupal\Core\Block\BlockManagerInterface $block_manager
   * @param \Drupal\Core\Render\RendererInterface $renderer
   * @param \Drupal\layout_plugin\Plugin\Layout\LayoutPluginManagerInterface $layout_plugin_manager
   * @param \Drupal\user\SharedTempStoreFactory $temp_store_factory
   */
  public function __construct(BlockManagerInterface $block_manager, RendererInterface $renderer, LayoutPluginManagerInterface $layout_plugin_manager, PanelsStorageManagerInterface $panels_storage_manager, SharedTempStoreFactory $temp_store_factory) {
    $this->blockManager = $block_manager;
    $this->renderer = $renderer;
    $this->layoutPluginManager = $layout_plugin_manager;
    $this->panelsStorage = $panels_storage_manager;
    $this->tempStore = $temp_store_factory->get('panels_ipe');
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
      $container->get('user.shared_tempstore')
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
   * Saves the current Panels display in the tempstore or real storage..
   *
   * @param \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant $panels_display
   *   The Panels display to be saved.
   * @param bool $temp
   *   Whether or not to save to temp store.
   *
   * @return \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant
   *   The Panels display that was saved.
   *
   * @throws \Drupal\user\TempStoreException
   *   If there are any issues manipulating the entry in the temp store.
   */
  protected function savePanelsDisplay(PanelsDisplayVariant $panels_display, $temp = TRUE) {
    $temp_store_key = $panels_display->id();

    // Save configuration to temp store.
    if ($temp) {
      $this->tempStore->set($temp_store_key, $panels_display->getConfiguration());
    }
    else {
      // Check to see if temp store has configuration saved.
      if ($variant_config = $this->tempStore->get($temp_store_key)) {
        // Delete the existing temp store value.
        $this->tempStore->delete($temp_store_key);
      }

      // Save to the real storage.
      $this->panelsStorage->save($panels_display);
    }

    return $panels_display;
  }

  /**
   * Removes any temporary changes to the variant.
   *
   * @param string $panels_display_id
   *   The id of the current Panels display.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
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
   * @return AjaxResponse
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
   * Updates the current Panels display based on the changes done in our app.
   *
   * @param \Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant $panels_display
   *   The current Panels display.
   * @param array $layout_model
   *   The decoded LayoutModel from our App.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   */
  protected function updatePanelsDisplay(PanelsDisplayVariant $panels_display, array $layout_model) {
    // Set our weight and region based on the metadata in our Backbone app.
    foreach ($layout_model['regionCollection'] as $region) {
      $weight = 0;
      foreach ($region['blockCollection'] as $block) {
        /** @var \Drupal\Core\Block\BlockBase $block_instance */
        $block_instance = $panels_display->getBlock($block['uuid']);

        $block_instance->setConfigurationValue('region', $region['name']);
        $block_instance->setConfigurationValue('weight', ++$weight);

        $panels_display->updateBlock($block['uuid'], $block_instance->getConfiguration());
      }
    }

    // Remove blocks that need removing.
    // @todo We should do this on the fly instead of at on save.
    foreach ($layout_model['deletedBlocks'] as $uuid) {
      $panels_display->removeBlock($uuid);
    }

    // Allow other modules to modify the display before saving based on the
    // contents of our $layout_model.
    $this->moduleHandler()->invokeAll('panels_ipe_panels_display_presave', [$panels_display, $layout_model]);

    // Save the variant and remove temp storage.
    $this->savePanelsDisplay($panels_display, FALSE);

    return new JsonResponse(['deletedBlocks' => []]);
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
  public function updateLayout($panels_storage_type, $panels_storage_id, Request $request) {
    $panels_display = $this->loadPanelsDisplay($panels_storage_type, $panels_storage_id);

    // Decode the request.
    $content = $request->getContent();
    if (!empty($content) && $layout_model = Json::decode($content)) {
      return $this->updatePanelsDisplay($panels_display, $layout_model);
    }
    else {
      return new JsonResponse(['success' => false], 400);
    }
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
  public function createLayout($panels_storage_type, $panels_storage_id, Request $request) {
    // For now, creating and updating a layout is the same thing.
    return $this->updateLayout($panels_storage_type, $panels_storage_id, $request);
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
    $command = new AppendCommand('.ipe-block-plugin-form', $form);
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
   *  @param string $type
   *   The Block Content UUID, if an entity already exists.
   *
   * @return NotFoundHttpException|Response
   */
  public function getBlockContentForm($panels_storage_type, $panels_storage_id, $type) {
    $storage = $this->entityTypeManager()->getStorage('block_content');

    // Create a new block of the given type.
    $block = $storage->create([
      'type' => $type
    ]);

    // Grab our Block Content Entity form handler.
    $form = $this->entityFormBuilder()->getForm($block, 'panels_ipe');

    // Return the rendered form as a proper Drupal AJAX response.
    $response = new AjaxResponse();
    $command = new AppendCommand('.ipe-block-type-form', $form);
    $response->addCommand($command);
    return $response;
  }

}
