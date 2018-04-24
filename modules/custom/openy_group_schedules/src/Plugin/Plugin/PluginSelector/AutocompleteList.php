<?php

namespace Drupal\openy_group_schedules\Plugin\Plugin\PluginSelector;

use Drupal\Core\Form\FormStateInterface;
use Drupal\plugin\PluginDefinition\PluginLabelDefinitionInterface;
use Drupal\plugin\PluginHierarchyTrait;
use Drupal\plugin\Plugin\Plugin\PluginSelector\AdvancedPluginSelectorBase;

/**
 * Provides a plugin selector using a <select> element.
 *
 * @PluginSelector(
 *   id = "plugin_autocomplete_list",
 *   label = @Translation("Autocomplete selection list")
 * )
 */
class AutocompleteList extends AdvancedPluginSelectorBase {

  use PluginHierarchyTrait;

  /**
   * {@inheritdoc}
   */
  protected function buildSelector(array $root_element, FormStateInterface $form_state, array $plugins) {
    $element = parent::buildSelector($root_element, $form_state, $plugins);

    /** @var \Drupal\Component\Plugin\PluginInspectionInterface[] $plugins */

    $options = $this->buildOptionsLevel($this->buildPluginHierarchy($this->selectablePluginDiscovery));

    $element['container']['plugin_id'] = array(
      '#ajax' => array(
        'callback' => array(get_class(), 'ajaxRebuildForm'),
        'effect' => 'fade',
        'event' => 'change',
        'trigger_as' => array(
          'name' => $options[$element['container']['change']['#name']],
        ),
      ),
      '#default_value' => $this->getSelectedPlugin() ? $options[$this->getSelectedPlugin()->getPluginId()] : NULL,
      '#options' => $options,
      '#required' => $this->isRequired(),
      '#title' => $this->getLabel(),
      '#description' => $this->getDescription(),
      '#type' => 'textfield',
      '#autocomplete_route_name' => 'openy_group_schedules.autocomplete',
      #'#autocomplete_route_parameters' => array('field_name' => $this->getSelectedPlugin()->getPluginId()),
    );

    return $element;
  }

  /**
   * Helper function for self::options().
   *
   * @param array $hierarchy
   *   A plugin ID hierarchy as returned by self::hierarchy().
   * @param integer $depth
   *   The depth of $hierarchy's top-level items as seen from the original
   *   hierarchy's root (this function is recursive), starting with 0.
   *
   * @return string[]
   *   Keys are plugin IDs.
   */
  protected function buildOptionsLevel(array $hierarchy, $depth = 0) {
    $plugin_definitions = $this->selectablePluginDiscovery->getDefinitions();
    $options = [];
    $prefix = $depth ? str_repeat('-', $depth) . ' ' : '';
    foreach ($hierarchy as $plugin_id => $child_plugin_ids) {
      $plugin_definition = $plugin_definitions[$plugin_id];
      $label = $plugin_definition instanceof PluginLabelDefinitionInterface ? $plugin_definition->getLabel() : $plugin_definition->getId();
      $options[$plugin_id] = $prefix . $label;
      $options += $this->buildOptionsLevel($child_plugin_ids, $depth + 1);
    }

    return $options;
  }
}
