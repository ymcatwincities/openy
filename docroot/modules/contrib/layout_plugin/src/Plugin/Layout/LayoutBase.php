<?php

/**
 * @file
 * Contains \Drupal\layout_plugin\Plugin\Layout\LayoutBase.
 */

namespace Drupal\layout_plugin\Plugin\Layout;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\layout_plugin\Layout;

/**
 * Provides a base class for Layout plugins.
 */
abstract class LayoutBase extends PluginBase implements LayoutInterface {

  /**
   * @var array
   * The layout configuration.
   */
  protected $configuration = [];

  /**
   * Get the plugin's description.
   *
   * @return string
   *   The layout description
   */
  public function getDescription() {
    return $this->pluginDefinition['description'];
  }

  /**
   * Get human-readable list of regions keyed by machine name.
   *
   * @return array
   *   An array of human-readable region names keyed by machine name.
   */
  public function getRegionNames() {
    return $this->pluginDefinition['region_names'];
  }

  /**
   * Get information on regions keyed by machine name.
   *
   * @return array
   *   An array of information on regions keyed by machine name.
   */
  public function getRegionDefinitions() {
    return $this->pluginDefinition['regions'];
  }

  /**
   * Get the base path for all resources.
   *
   * @return string
   *   The full base to all resources.
   */
  public function getBasePath() {
    return isset($this->pluginDefinition['path']) && $this->pluginDefinition['path'] ? $this->pluginDefinition['path'] : FALSE;
  }

  /**
   * Get the full path to the icon image.
   *
   * This can optionally be used in the user interface to show the layout of
   * regions visually.
   *
   * @return string
   *   The full path to preview image file.
   */
  public function getIconFilename() {
    return isset($this->pluginDefinition['icon']) && $this->pluginDefinition['icon'] ? $this->pluginDefinition['icon'] : FALSE;
  }

  /**
   * Get the asset library name.
   *
   * @return string
   *   The asset library.
   */
  public function getLibrary() {
    return isset($this->pluginDefinition['library']) && $this->pluginDefinition['library'] ? $this->pluginDefinition['library'] : FALSE;
  }

  /**
   * Get the theme function for rendering this layout.
   *
   * @return string
   *   Theme function name.
   */
  public function getTheme() {
    return isset($this->pluginDefinition['theme']) && $this->pluginDefinition['theme'] ? $this->pluginDefinition['theme'] : FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $regions) {
    $build = $regions;
    $build['#layout'] = $this->getPluginDefinition();
    $build['#settings'] = $this->getConfiguration();
    if ($theme = $this->getTheme()) {
      $build['#theme'] = $theme;
    }
    if ($library = $this->getLibrary()) {
      $build['#attached']['library'][] = $library;
    }
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration = $form_state->getValues();
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return array_merge($this->defaultConfiguration(), $this->configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * @{inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }
}
