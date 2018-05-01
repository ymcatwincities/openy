<?php

namespace Drupal\openy_group_schedules\Plugin\Plugin\PluginSelector;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AlertCommand;
use Drupal\Core\Ajax\ChangedCommand;
use Drupal\Core\Ajax\DataCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormStateInterface;
use Drupal\plugin\PluginDefinition\PluginLabelDefinitionInterface;
use Drupal\plugin\PluginHierarchyTrait;
use Drupal\plugin\Plugin\Plugin\PluginSelector\AdvancedPluginSelectorBase;

/**
 * Provides a plugin selector using a <autocomplete> element.
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
    $element['container']['plugin_id'] = [
      '#ajax' => [
        'callback' => [$this, 'ajaxRebuildForm'],
        'effect' => 'fade',
        'event' => 'autocompleteclose',
        '#limit_validation_errors' => [
          [
            $root_element['#parents'], ['select', 'plugin_id'],
          ],
        ],
        '#submit' => [[get_class(), 'rebuildForm']],
      ],
      '#default_value' => $this->getSelectedPlugin() ? $this->getSelectedPlugin()->getPluginDefinition()['admin_label'] . ' (' . $this->getSelectedPlugin()->getPluginId() . ')' : NULL,
      '#required' => $this->isRequired(),
      '#title' => $this->getLabel(),
      '#description' => $this->getDescription(),
      '#type' => 'textfield',
      '#autocomplete_route_name' => 'openy_group_schedules.autocomplete',
      '#autocomplete_route_parameters' => [],
    ];

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

  /**
   * {@inheritdoc}
   */
  public function validateSelectorForm(array &$plugin_selector_form, FormStateInterface $plugin_selector_form_state) {
    $this->assertSubformState($plugin_selector_form_state);
    $plugin_id = $plugin_selector_form_state
      ->getValue(['container', 'select', 'container', 'plugin_id']);
    $match = preg_match('/\s\(([a-zA-Z0-9\:\-\_]+)\)$/', $plugin_id, $matches);
    if ($match && isset($matches[1])) {
      $plugin_id = $matches[1];
    }
    $selected_plugin = $this->getSelectedPlugin();
    if (!$selected_plugin && $plugin_id || $selected_plugin && $plugin_id != $selected_plugin->getPluginId()) {
      // Keep track of all previously selected plugins so their configuration
      // does not get lost.
      if (isset($this->getPreviouslySelectedPlugins()[$plugin_id])) {
        $this->setSelectedPlugin($this->getPreviouslySelectedPlugins()[$plugin_id]);
      }
      elseif ($plugin_id) {
        $this->setSelectedPlugin($this->selectablePluginFactory->createInstance($plugin_id));
      }
      else {
        $this->resetSelectedPlugin();
      }

      // If a (different) plugin was chosen and its form must be displayed,
      // rebuild the form.
      if ($this->getCollectPluginConfiguration() && $this->getSelectedPlugin() instanceof PluginFormInterface) {
        $plugin_selector_form_state->setRebuild();
      }
    }
    // If no (different) plugin was chosen, delegate validation to the plugin.
    elseif ($this->getCollectPluginConfiguration() && $selected_plugin instanceof PluginFormInterface) {
      $selected_plugin_form = &$plugin_selector_form['container']['plugin_form'];
      $selected_plugin_form_state = SubformState::createForSubform($selected_plugin_form, $plugin_selector_form, $plugin_selector_form_state);
      $selected_plugin->validateConfigurationForm($selected_plugin_form, $selected_plugin_form_state);
    }
  }

}
