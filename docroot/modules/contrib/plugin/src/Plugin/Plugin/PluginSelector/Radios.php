<?php

namespace Drupal\plugin\Plugin\Plugin\PluginSelector;

use Drupal\Core\Form\FormStateInterface;
use Drupal\plugin\PluginDefinition\PluginLabelDefinitionInterface;

/**
 * Provides a plugin selector using a radio buttons.
 *
 * @PluginSelector(
 *   id = "plugin_radios",
 *   label = @Translation("Radio buttons")
 * )
 */
class Radios extends AdvancedPluginSelectorBase {

  /**
   * {@inheritdoc}
   */
  public function buildSelectorForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildSelectorForm($form, $form_state);
    $form['clear'] = array(
      '#markup' => '<div style="clear: both;"></div>',
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function buildSelector(array $root_element, FormStateInterface $form_state, array $plugins) {
    $element = parent::buildSelector($root_element, $form_state, $plugins);
    /** @var \Drupal\Component\Plugin\PluginInspectionInterface[] $plugins */
    $plugin_options = [];
    foreach ($plugins as $plugin) {
      $plugin_definition = $this->selectablePluginType->ensureTypedPluginDefinition($plugin->getPluginDefinition());
      $plugin_options[$plugin->getPluginId()] = $plugin_definition instanceof PluginLabelDefinitionInterface ? $plugin_definition->getLabel() : $plugin_definition->getId();
    }
    natcasesort($plugin_options);
    $element['container']['plugin_id'] = array(
      '#ajax' => array(
        'callback' => array(get_class(), 'ajaxRebuildForm'),
        'effect' => 'fade',
        'event' => 'change',
        'progress' => 'none',
        'trigger_as' => array(
          'name' => $element['container']['change']['#name'],
        ),
      ),
      '#attached' => [
        'library' => ['plugin/plugin_selector.plugin_radios'],
      ],
      '#default_value' => $this->getSelectedPlugin() ? $this->getSelectedPlugin()->getPluginId() : NULL,
      '#empty_value' => 'select',
      '#options' => $plugin_options ,
      '#required' => $this->isRequired(),
      '#title' => $this->getlabel(),
      '#description' => $this->getDescription(),
      '#type' => 'radios',
    );

    return $element;
  }

}
