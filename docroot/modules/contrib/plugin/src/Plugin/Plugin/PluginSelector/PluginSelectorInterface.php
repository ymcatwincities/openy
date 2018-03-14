<?php

namespace Drupal\plugin\Plugin\Plugin\PluginSelector;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\Component\Plugin\Factory\FactoryInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\plugin\PluginType\PluginTypeInterface;

/**
 * Defines a plugin to select and configure another plugin.
 */
interface PluginSelectorInterface extends PluginInspectionInterface, ConfigurablePluginInterface {

  /**
   * Sets the human-readable label.
   *
   * @param string $label
   *
   * @return $this
   */
  public function setLabel($label);

  /**
   * Gets the human-readable label.
   *
   * @return string
   */
  public function getLabel();

  /**
   * Sets the human-readable description.
   *
   * @param string $description
   *
   * @return $this
   */
  public function setDescription($description);

  /**
   * Gets the human-readable description.
   *
   * @return string
   */
  public function getDescription();

  /**
   * Sets whether a plugin must be selected.
   *
   * @param bool $required
   *
   * @return $this
   */
  public function setRequired($required = TRUE);

  /**
   * Returns whether a plugin must be selected.
   *
   * @return bool
   */
  public function isRequired();

  /**
   * Sets whether a plugin's configuration must be collected.
   *
   * @param bool $collect
   *
   * @return $this
   */
  public function setCollectPluginConfiguration($collect = TRUE);

  /**
   * Gets whether a plugin's configuration must be collected.
   *
   * @return bool
   */
  public function getCollectPluginConfiguration();

  /**
   * Sets whether previously selected plugins must be kept.
   *
   * @param bool $keep
   *
   * @return $this
   *
   * @see self::getPreviouslySelectedPlugins()
   */
  public function setKeepPreviouslySelectedPlugins($keep = TRUE);

  /**
   * Gets whether previously selected plugins must be kept.
   *
   * @return bool
   */
  public function getKeepPreviouslySelectedPlugins();

  /**
   * Sets previously selected plugins.
   *
   * @param \Drupal\Component\Plugin\PluginInspectionInterface[] $plugins
   *
   * @return $this
   *
   * @see self::setKeepPreviouslySelectedPlugins()
   * @see self::setCollectPluginConfiguration()
   */
  public function setPreviouslySelectedPlugins(array $plugins);

  /**
   * Gets previously selected plugins.
   *
   * @return \Drupal\Component\Plugin\PluginInspectionInterface[]
   *
   * @see self::setKeepPreviouslySelectedPlugins
   */
  public function getPreviouslySelectedPlugins();

  /**
   * Gets the selected plugin.
   *
   * @return \Drupal\Component\Plugin\PluginInspectionInterface
   */
  public function getSelectedPlugin();

  /**
   * Sets the selected plugin.
   *
   * @param \Drupal\Component\Plugin\PluginInspectionInterface $plugin
   *
   * @return $this
   */
  public function setSelectedPlugin(PluginInspectionInterface $plugin);

  /**
   * Resets the selected plugin.
   *
   * This resets any default or explicitly set selected plugin.
   *
   * @return $this
   */
  public function resetSelectedPlugin();

  /**
   * Sets the selectable plugin type.
   *
   * @param \Drupal\plugin\PluginType\PluginTypeInterface $plugin_type
   *   The type of which to select plugins.
   *
   * @return $this
   */
  public function setSelectablePluginType(PluginTypeInterface $plugin_type);

  /**
   * Overrides the plugin type's discovery.
   *
   * @param \Drupal\Component\Plugin\Discovery\DiscoveryInterface
   *
   * @return $this
   *
   * @throws \RuntimeException
   *   Thrown if the plugin type was not set using
   *   self::setSelectablePluginType().
   */
  public function setSelectablePluginDiscovery(DiscoveryInterface $plugin_discovery);

  /**
   * Overrides the plugin type's factory.
   *
   * @param \Drupal\Component\Plugin\Factory\FactoryInterface $plugin_factory
   *
   * @return $this
   *
   * @throws \RuntimeException
   *   Thrown if the plugin type was not set using
   *   self::setSelectablePluginType().
   */
  public function setSelectablePluginFactory(FactoryInterface $plugin_factory);

  /**
   * Builds the selector form.
   *
   * @param mixed[] $plugin_selector_form
   *   Any suggested form elements to build upon. May be ignored.
   * @param \Drupal\Core\Form\SubformStateInterface|\Drupal\Core\Form\FormStateInterface $plugin_selector_form_state
   *   The form state for $plugin_selector_form and the return value. This often
   *   is not the complete (global) form state and SHOULD be
   *   \Drupal\Core\Form\SubformStateInterface (added in Drupal 8.2.0)
   *
   * @return mixed[]
   *   The form structure.
   *
   * @throws \RuntimeException
   *   Thrown if the plugin type was not set using
   *   self::setSelectablePluginType().
   */
  public function buildSelectorForm(array $plugin_selector_form, FormStateInterface $plugin_selector_form_state);

  /**
   * Validates the selector form.
   *
   * @param mixed[] $plugin_selector_form
   *   The selector form as built by static::buildSelectorForm().
   * @param \Drupal\Core\Form\SubformStateInterface|\Drupal\Core\Form\FormStateInterface $plugin_selector_form_state
   *   The form state for $plugin_selector_form. This often is not the complete
   *   (global) form state and SHOULD be \Drupal\Core\Form\SubformStateInterface
   *   (added in Drupal 8.2.0).
   */
  public function validateSelectorForm(array &$plugin_selector_form, FormStateInterface $plugin_selector_form_state);

  /**
   * Submits the selector form.
   *
   * @param mixed[] $plugin_selector_form
   *   The selector form as built by static::buildSelectorForm().
   * @param \Drupal\Core\Form\SubformStateInterface|\Drupal\Core\Form\FormStateInterface $plugin_selector_form_state
   *   The form state for $plugin_selector_form. This often is not the complete
   *   (global) form state and SHOULD be \Drupal\Core\Form\SubformStateInterface
   *   (added in Drupal 8.2.0)
   */
  public function submitSelectorForm(array &$plugin_selector_form, FormStateInterface $plugin_selector_form_state);

}
