<?php

namespace Drupal\openy_digital_signage_screen_content\Controller;

use Drupal\panels_ipe\Controller\PanelsIPEPageController;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Contains altered methods for Panels IPE.
 */
class OpenYDSPanelsIPEPageController extends PanelsIPEPageController {

  /**
   * Check is entity a screen content or not.
   *
   * @param string $panels_storage_id
   *   The id within the storage plugin for the requested Panels display.
   *
   * @return bool
   *   Status.
   */
  public function isEntityScreenContent($panels_storage_id) {
    $storage_keys = explode(':', $panels_storage_id);
    $entity_manager = \Drupal::entityTypeManager();
    $entity = $entity_manager->getStorage('node')
      ->load($storage_keys[1]);
    return $entity->bundle() == 'screen_content';
  }

  /**
   * {@inheritdoc}
   */
  public function getLayoutsData($panels_storage_type, $panels_storage_id) {
    if (!$this->isEntityScreenContent($panels_storage_id)) {
      return parent::getLayoutsData($panels_storage_type, $panels_storage_id);
    }

    $panels_display = $this->loadPanelsDisplay($panels_storage_type, $panels_storage_id);

    // Get the current layout.
    $current_layout_id = $panels_display->getLayout()->getPluginId();

    // Get a list of all available layouts.
    $layouts = $this->layoutPluginManager->getDefinitions();
    $base_path = base_path();
    $data = [];
    $supported_layouts = [
      'OpenY Digital Signage',
      'OpenY Room Entry Screen',
    ];
    foreach ($layouts as $id => $layout) {
      if (!in_array($layout->getCategory(), $supported_layouts)) {
        continue;
      }
      $icon = $layout->getIconPath() ?: drupal_get_path('module', 'panels') . '/layouts/no-layout-preview.png';
      $data[] = [
        'id' => $id,
        'label' => $layout->getLabel(),
        'icon' => $base_path . $icon,
        'current' => $id == $current_layout_id,
        'category' => $layout->getCategory(),
      ];
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function getLayouts($panels_storage_type, $panels_storage_id) {
    // Get the layouts data.
    $data = $this->getLayoutsData($panels_storage_type, $panels_storage_id);

    // Return a structured JSON response for our Backbone App.
    return new JsonResponse($data);
  }

  /**
   * {@inheritdoc}
   */
  public function getBlockPluginsData($panels_storage_type, $panels_storage_id) {
    if (!$this->isEntityScreenContent($panels_storage_id)) {
      return parent::getBlockPluginsData($panels_storage_type, $panels_storage_id);
    }
    $panels_display = $this->loadPanelsDisplay($panels_storage_type, $panels_storage_id);

    // Get block plugin definitions from the server.
    $definitions = $this->blockManager->getDefinitionsForContexts($panels_display->getContexts());

    // Assemble our relevant data.
    $blocks = [];
    $supported_block_bundles = [
      'digital_signage_block_free_html',
      'digital_signage_promotional',
    ];
    $supported_categories = ['Digital Signage', 'Custom'];
    foreach ($definitions as $plugin_id => $definition) {
      // Don't add broken Blocks.
      if ($plugin_id == 'broken') {
        continue;
      }
      // Allow only specific categories.
      if (!in_array($definition['category'], $supported_categories)) {
        continue;
      }
      if ($definition['id'] == 'block_content' && !empty($definition['config_dependencies']['content'][0])) {
        $block_ids = explode(':', $definition['config_dependencies']['content'][0]);

        // Skip all plugins except next.
        if (!in_array($block_ids[1], $supported_block_bundles)) {
          continue;
        }
      }

      $blocks[] = [
        'plugin_id' => $plugin_id,
        'label' => $definition['admin_label'],
        'category' => $definition['category'],
        'id' => $definition['id'],
        'provider' => $definition['provider'],
      ];
    }

    return $blocks;
  }

  /**
   * {@inheritdoc}
   */
  public function getBlockPlugins($panels_storage_type, $panels_storage_id) {
    // Get the block plugins data.
    $blocks = $this->getBlockPluginsData($panels_storage_type, $panels_storage_id);

    // Return a structured JSON response for our Backbone App.
    return new JsonResponse($blocks);
  }

  /**
   * {@inheritdoc}
   */
  public function getBlockContentTypesData($panels_storage_type, $panels_storage_id) {
    if (!$this->isEntityScreenContent($panels_storage_id)) {
      return parent::getBlockContentTypesData($panels_storage_type, $panels_storage_id);
    }
    // Assemble our relevant data.
    $types = $this->entityTypeManager()
      ->getStorage('block_content_type')
      ->loadMultiple();
    $data = [];

    $available_types = [
      'digital_signage_block_free_html',
      'digital_signage_promotional',
    ];
    /* @var \Drupal\block_content\BlockContentTypeInterface $definition */
    foreach ($types as $id => $definition) {
      if (!in_array($definition->id(), $available_types)) {
        continue;
      }
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
   * {@inheritdoc}
   */
  public function getBlockContentTypes($panels_storage_type, $panels_storage_id) {
    // Get the block content types data.
    $data = $this->getBlockContentTypesData($panels_storage_type, $panels_storage_id);

    // Return a structured JSON response for our Backbone App.
    return new JsonResponse($data);
  }

}
