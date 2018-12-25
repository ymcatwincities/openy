<?php

namespace Drupal\plugin;

use Drupal\plugin\PluginDefinition\PluginDefinitionInterface;
use Drupal\plugin\PluginDefinition\PluginLabelDefinitionInterface;
use Drupal\plugin\PluginDefinition\PluginHierarchyDefinitionInterface;
use Drupal\plugin\PluginDiscovery\TypedDiscoveryInterface;

/**
 * Supports handling hierarchical plugins.
 */
trait PluginHierarchyTrait {

  /**
   * Returns a hierarchical plugin representation.
   *
   * @param \Drupal\plugin\PluginDiscovery\TypedDiscoveryInterface $plugin_discovery
   *   The typed plugin discovery.
   *
   * @return array[]
   *   A possibly infinitely nested associative array. Keys are plugin IDs and
   *   values are arrays of similar structure as this method's return value.
   */
  protected function buildPluginHierarchy(TypedDiscoveryInterface $plugin_discovery) {
    $parents = [];
    $children = [];
    $definitions = $plugin_discovery->getDefinitions();
    uasort($definitions, function(PluginDefinitionInterface $definition_a, PluginDefinitionInterface $definition_b) {
      $label_a = $definition_a instanceof PluginLabelDefinitionInterface ? $definition_a->getLabel() : $definition_a->getId();
      $label_b = $definition_b instanceof PluginLabelDefinitionInterface ? $definition_b->getLabel() : $definition_b->getId();

      return strcmp($label_a, $label_b);
    });
    foreach ($definitions as $plugin_id => $plugin_definition) {
      if ($plugin_definition instanceof PluginHierarchyDefinitionInterface && $plugin_definition->getParentId()) {
        $children[$plugin_definition->getParentId()][] = $plugin_id;
      }
      else {
        $parents[] = $plugin_id;
      }
    }

    return $this->buildPluginHierarchyLevel($parents, $children);
  }

  /**
   * Helper function for self::hierarchy().
   *
   * @param array $parent_plugin_ids
   *   An array with IDs of plugins that are part of the same hierarchy level.
   * @param array $child_plugin_ids
   *   Keys are plugin IDs. Values are arrays with those plugin's child
   *   plugin IDs.
   *
   * @return array[]
   *   The return value is identical to that of self::hierarchy().
   */
  protected function buildPluginHierarchyLevel(array $parent_plugin_ids, array $child_plugin_ids) {
    $hierarchy = [];
    foreach ($parent_plugin_ids as $plugin_id) {
      $hierarchy[$plugin_id] = isset($child_plugin_ids[$plugin_id]) ? $this->buildPluginHierarchyLevel($child_plugin_ids[$plugin_id], $child_plugin_ids) : [];
    }

    return $hierarchy;
  }

}
