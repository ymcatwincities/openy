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
  public function getLayouts($panels_storage_type, $panels_storage_id) {
    if (!$this->isEntityScreenContent($panels_storage_id)) {
      return parent::getLayouts($panels_storage_type, $panels_storage_id);
    }

    $panels_display = $this->loadPanelsDisplay($panels_storage_type, $panels_storage_id);

    // Get the current layout.
    $current_layout_id = $panels_display->getLayout()->getPluginId();

    // Get a list of all available layouts.
    $layouts = $this->layoutPluginManager->getDefinitions();
    $base_path = base_path();
    $data = [];
    foreach ($layouts as $id => $layout) {
      if ($layout['category'] != 'OpenY Digital Signage') {
        continue;
      }
      $icon = !empty($layout['icon']) ? $layout['icon'] : drupal_get_path('module', 'panels') . '/images/no-layout-preview.png';
      $data[] = [
        'id' => $id,
        'label' => $layout['label'],
        'icon' => $base_path . $icon,
        'current' => $id == $current_layout_id,
        'category' => $layout['category'],
      ];
    }

    // Return a structured JSON response for our Backbone App.
    return new JsonResponse($data);
  }

  /**
   * {@inheritdoc}
   */
  public function getBlockPlugins($panels_storage_type, $panels_storage_id) {
    if (!$this->isEntityScreenContent($panels_storage_id)) {
      return parent::getBlockPlugins($panels_storage_type, $panels_storage_id);
    }
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
      // Skip all plugins except next.
      $plugins = ['block_content'];
      if (!in_array($plugin_id, $plugins)) {
//        continue;
      }
      // Allow only specific categories.
      $categories = ['Digital Signage', 'Custom'];
      if (!in_array($definition['category'], $categories)) {
        continue;
      }
      if ($definition['category'] == 'Custom' && $plugin_id == 'block_content') {
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
   * {@inheritdoc}
   */
  public function getBlockContentTypes($panels_storage_type, $panels_storage_id) {
    if (!$this->isEntityScreenContent($panels_storage_id)) {
      return parent::getBlockContentTypes($panels_storage_type, $panels_storage_id);
    }
    // Assemble our relevant data.
    $types = $this->entityTypeManager()
      ->getStorage('block_content_type')
      ->loadMultiple();
    $data = [];

    // @todo remove basic.
    $available_types = [
      'basic',
      'digital_signage_block_free_html',
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

}