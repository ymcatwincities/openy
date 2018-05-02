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
use Drupal\Component\Utility\Html;

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
        '#submit' => [[$this, 'rebuildForm']],
      ],
      '#default_value' => $this->getSelectedPlugin() ? $this->getSelectedPlugin()->getPluginDefinition()['admin_label'] . ' (' . $this->getSelectedPlugin()->getPluginId() . ')' : NULL,
      // '#default_value' => $this->getSelectedPlugin() ? $this->getSelectedPlugin()->getPluginId() : NULL,
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
   * {@inheritdoc}
   */
  public function validateSelectorForm(array &$plugin_selector_form, FormStateInterface $plugin_selector_form_state) {
    $this->setPluginId($plugin_selector_form_state);
    parent::validateSelectorForm($plugin_selector_form, $plugin_selector_form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitSelectorForm(array &$plugin_selector_form, FormStateInterface $plugin_selector_form_state) {
    $this->setPluginId($plugin_selector_form_state);
    parent::submitSelectorForm($plugin_selector_form, $plugin_selector_form_state);
  }

  public function setPluginId(FormStateInterface &$plugin_selector_form_state) {
    $this->assertSubformState($plugin_selector_form_state);
    $plugin_id = $plugin_selector_form_state
      ->getValue(['container', 'select', 'container', 'plugin_id']);
    $match = preg_match('/\s\(([a-zA-Z0-9\:\-\_]+)\)$/', $plugin_id, $matches);
    if ($match && isset($matches[1])) {
      $plugin_id = $matches[1];
      $plugin_selector_form_state
        ->setValue(['container', 'select', 'container', 'plugin_id'], $plugin_id);
    }
  }

}
