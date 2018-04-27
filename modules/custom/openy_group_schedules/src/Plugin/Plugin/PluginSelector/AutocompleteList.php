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

    /** @var \Drupal\Component\Plugin\PluginInspectionInterface[] $plugins */

    $options = $this->buildOptionsLevel($this->buildPluginHierarchy($this->selectablePluginDiscovery));
    $element['container']['plugin_id'] = [
      '#ajax' => [
        'callback' => [$this, 'ajaxRebuildForm'],
        'effect' => 'fade',
        'event' => 'change',
        'trigger_as' => [
          'name' => $element['container']['change']['#name'],
        ],
      ],
      '#default_value' => $this->getSelectedPlugin() ? $this->getSelectedPlugin()->getPluginId() : NULL,
      //'#options' => $options,
      '#required' => $this->isRequired(),
      '#title' => $this->getLabel(),
      '#description' => $this->getDescription(),
      '#type' => 'textfield',
      //'#autocomplete_route_name' => 'openy_group_schedules.autocomplete',
      //'#autocomplete_route_parameters' => [],
    ];
    $element['container']['plugin_name'] = [
      '#ajax' => [
        'callback' => [$this, 'ajaxChangeHiddenFields'],
        'effect' => 'fade',
        'event' => 'change',
        /*'trigger_as' => [
          'name' => $element['container']['plugin_id']['#name'],
        ],*/
      ],
      '#default_value' => $this->getSelectedPlugin() ? $options[$this->getSelectedPlugin()->getPluginId()] : NULL,
      //'#options' => $options,
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
   * Implements form AJAX callback.
   */
  public function ajaxChangeHiddenFields(array &$complete_form, FormStateInterface $complete_form_state) {

    $triggering_element = $complete_form_state->getTriggeringElement();
    $triggered_value = $triggering_element['#value'];

    $form_parents = array_slice($triggering_element['#array_parents'], 0, -1);
    $form_parents[] = 'plugin_id';

    $element = NestedArray::getValue($complete_form, $form_parents);
    $element->setValue($triggered_value);

    $response = new AjaxResponse();
    $response->addCommand(new DataCommand('input[data-drupal-selector="edit-field-header-content-0-subform-field-prgf-schedules-ref-0-plugin-selector-container-select-container-plugin-id"]', 'value', $triggered_value));
    $response->addCommand(new ChangedCommand('input[data-drupal-selector="edit-field-header-content-0-subform-field-prgf-schedules-ref-0-plugin-selector-container-select-container-plugin-id"]'));


    $response->addCommand(new AlertCommand('input[data-drupal-selector="edit-field-header-content-0-subform-field-prgf-schedules-ref-0-plugin-selector-container-select-container-plugin-id"]'));
    return $response;
    //print '<pre>' . print_r($root_element, 1) . '</pre>' ;

    /** @var AjaxResponse $response */
    $response = new AjaxResponse();
    $response->addCommand(new AlertCommand(t($triggered_value)));


    return $response;
  }

  /**
   * Implements form AJAX callback.
   */
  public static function ajaxRebuildForm(array &$complete_form, FormStateInterface $complete_form_state) {
    $blockManager = \Drupal::service('plugin.manager.block');
    $contextRepository = \Drupal::service('context.repository');

    // Get blocks definition
    $definitions = $blockManager->getDefinitionsForContexts($contextRepository->getAvailableContexts());
    $options = [];
    foreach ($definitions as $machine_name => $definition) {
      $options[$definition['admin_label']] = $machine_name;
    }

    $triggering_element = $complete_form_state->getTriggeringElement();

    $form_parents = array_slice($triggering_element['#array_parents'], 0, -3);
    $root_element = NestedArray::getValue($complete_form, $form_parents);

    $response = new AjaxResponse();
    $response->addCommand(new ReplaceCommand(sprintf('[data-drupal-selector="%s"]', $root_element['plugin_form']['#attributes']['data-drupal-selector']), $root_element['plugin_form']));

    return $response;
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
