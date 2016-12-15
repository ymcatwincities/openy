<?php

namespace Drupal\layout_plugin\Plugin\Layout;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Provides a base class for Layout plugins.
 */
abstract class LayoutBase extends PluginBase implements LayoutInterface, ConfigurablePluginInterface, PluginFormInterface {

  /**
   * The layout configuration.
   *
   * @var array
   */
  protected $configuration = [];

  /**
   * Gets the human-readable name.
   *
   * @return \Drupal\Core\Annotation\Translation|NULL
   *   The human-readable name.
   */
  public function getLabel() {
    return $this->pluginDefinition['label'];
  }

  /**
   * Gets the optional description for advanced layouts.
   *
   * @return \Drupal\Core\Annotation\Translation|NULL
   *   The layout description.
   */
  public function getDescription() {
    return isset($this->pluginDefinition['description']) ? $this->pluginDefinition['description'] : NULL;
  }

  /**
   * Gets the human-readable category.
   *
   * @return \Drupal\Core\Annotation\Translation
   *   The human-readable category.
   */
  public function getCategory() {
    return $this->pluginDefinition['category'];
  }

  /**
   * Gets human-readable list of regions keyed by machine name.
   *
   * @return \Drupal\Core\Annotation\Translation[]
   *   An array of human-readable region names keyed by machine name.
   */
  public function getRegionNames() {
    return $this->pluginDefinition['region_names'];
  }

  /**
   * Gets information on regions keyed by machine name.
   *
   * @return array
   *   An array of information on regions keyed by machine name.
   */
  public function getRegionDefinitions() {
    return $this->pluginDefinition['regions'];
  }

  /**
   * Gets the path to resources like icon or template.
   *
   * @return string|NULL
   *   The path relative to the Drupal root.
   */
  public function getBasePath() {
    return isset($this->pluginDefinition['path']) ? $this->pluginDefinition['path'] : NULL;
  }

  /**
   * Gets the path to the preview image.
   *
   * This can optionally be used in the user interface to show the layout of
   * regions visually.
   *
   * @return string|NULL
   *   The path to preview image file.
   */
  public function getIconFilename() {
    return isset($this->pluginDefinition['icon']) ? $this->pluginDefinition['icon'] : NULL;
  }

  /**
   * Get the asset library.
   *
   * @return string|NULL
   *   The asset library.
   */
  public function getLibrary() {
    return isset($this->pluginDefinition['library']) ? $this->pluginDefinition['library'] : NULL;
  }

  /**
   * Gets the theme hook used to render this layout.
   *
   * @return string|NULL
   *   Theme hook.
   */
  public function getThemeHook() {
    return isset($this->pluginDefinition['theme']) ? $this->pluginDefinition['theme'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $regions) {
    $build = array_intersect_key($regions, $this->getRegionDefinitions());
    $build['#layout'] = $this->getPluginDefinition();
    $build['#settings'] = $this->getConfiguration();
    if ($theme = $this->getThemeHook()) {
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
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return isset($this->configuration['dependencies']) ? $this->configuration['dependencies'] : [];
  }

}
