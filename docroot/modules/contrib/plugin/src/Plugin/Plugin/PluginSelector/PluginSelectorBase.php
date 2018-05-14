<?php

namespace Drupal\plugin\Plugin\Plugin\PluginSelector;

use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\Component\Plugin\Factory\FactoryInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\plugin\DefaultPluginResolver\DefaultPluginResolverInterface;
use Drupal\plugin\Form\SubformHelperTrait;
use Drupal\plugin\PluginDiscovery\TypedDefinitionEnsuringPluginDiscoveryDecorator;
use Drupal\plugin\PluginType\PluginTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a base plugin selector.
 *
 * Plugins extending this class should provide a configuration schema that
 * extends
 * plugin_selector.plugin_configuration.plugin_selector.plugin_selector_base.
 */
abstract class PluginSelectorBase extends PluginBase implements PluginSelectorInterface, ContainerFactoryPluginInterface {

  use SubformHelperTrait;

  /**
   * The default plugin resolver.
   *
   * @var \Drupal\plugin\DefaultPluginResolver\DefaultPluginResolverInterface
   */
  protected $defaultPluginResolver;

  /**
   * The previously selected plugins.
   *
   * @var \Drupal\Component\Plugin\PluginInspectionInterface[]
   */
  protected $previouslySelectedPlugins = [];

  /**
   * The plugin discovery of selectable plugins.
   *
   * @var \Drupal\plugin\PluginDiscovery\TypedDiscoveryInterface
   */
  protected $selectablePluginDiscovery;

  /**
   * The selectable plugin factory.
   *
   * @var \Drupal\Component\Plugin\Factory\FactoryInterface
   */
  protected $selectablePluginFactory;

  /**
   * The plugin type of which to select plugins.
   *
   * @var \Drupal\plugin\PluginType\PluginTypeInterface
   */
  protected $selectablePluginType;

  /**
   * The selected plugin.
   *
   * @var \Drupal\Component\Plugin\PluginInspectionInterface
   */
  protected $selectedPlugin;

  /**
   * Constructs a new instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\plugin\DefaultPluginResolver\DefaultPluginResolverInterface $default_plugin_resolver
   *   The default plugin resolver.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, DefaultPluginResolverInterface $default_plugin_resolver) {
    $configuration += $this->defaultConfiguration();
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->defaultPluginResolver = $default_plugin_resolver;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('plugin.default_plugin_resolver'));
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'description' => NULL,
      'label' => NULL,
      'required' => FALSE,
      'collect_plugin_configuration' => TRUE,
      'keep_previously_selected_plugins' => TRUE,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setLabel($label) {
    $this->configuration['label'] = $label;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->configuration['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function setDescription($description) {
    $this->configuration['description'] = $description;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->configuration['description'];
  }

  /**
   * {@inheritdoc}
   */
  public function setRequired($required = TRUE) {
    $this->configuration['required'] = $required;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isRequired() {
    return $this->configuration['required'];
  }

  /**
   * {@inheritdoc}
   */
  public function setCollectPluginConfiguration($collect = TRUE) {
    $this->configuration['collect_plugin_configuration'] = $collect;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCollectPluginConfiguration() {
    return $this->configuration['collect_plugin_configuration'];
  }

  /**
   * {@inheritdoc}
   */
  public function setKeepPreviouslySelectedPlugins($keep = TRUE) {
    $this->configuration['keep_previously_selected_plugins'] = $keep;
    if ($keep === FALSE) {
      $this->setPreviouslySelectedPlugins([]);
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getKeepPreviouslySelectedPlugins() {
    return $this->configuration['keep_previously_selected_plugins'];
  }

  /**
   * {@inheritdoc}
   */
  public function setPreviouslySelectedPlugins(array $plugins) {
    $this->previouslySelectedPlugins = $plugins;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPreviouslySelectedPlugins() {
    return $this->previouslySelectedPlugins;
  }

  /**
   * {@inheritdoc}
   */
  public function getSelectedPlugin() {
    return $this->selectedPlugin;
  }

  /**
   * {@inheritdoc}
   */
  public function setSelectedPlugin(PluginInspectionInterface $plugin) {
    $this->validateSelectablePluginType();
    $this->selectedPlugin = $plugin;
    if ($this->getKeepPreviouslySelectedPlugins()) {
      $this->previouslySelectedPlugins[$plugin->getPluginId()] = $plugin;
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function resetSelectedPlugin() {
    $this->selectedPlugin = NULL;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setSelectablePluginType(PluginTypeInterface $plugin_type) {
    $this->selectablePluginDiscovery = new TypedDefinitionEnsuringPluginDiscoveryDecorator($plugin_type);
    $this->selectablePluginFactory = $plugin_type->getPluginManager();
    $this->selectablePluginType = $plugin_type;
    $default_plugin = $this->defaultPluginResolver->createDefaultPluginInstance($plugin_type);
    if ($default_plugin) {
      $this->setSelectedPlugin($default_plugin);
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setSelectablePluginDiscovery(DiscoveryInterface $plugin_discovery) {
    $this->validateSelectablePluginType();
    $this->selectablePluginDiscovery = new TypedDefinitionEnsuringPluginDiscoveryDecorator($this->selectablePluginType, $plugin_discovery);

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setSelectablePluginFactory(FactoryInterface $plugin_factory) {
    $this->validateSelectablePluginType();
    $this->selectablePluginFactory = $plugin_factory;

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function buildSelectorForm(array $plugin_selector_form, FormStateInterface $plugin_selector_form_state) {
    $this->assertSubformState($plugin_selector_form_state);
    $this->validateSelectablePluginType();

    return [];
  }

  /**
   * Validates the selectable plugin type.
   *
   * @throw \RuntimeException
   */
  protected function validateSelectablePluginType() {
    if (!$this->selectablePluginType) {
      throw new \RuntimeException('A plugin type must be set through static::setSelectablePluginType() first.');
    }
  }

}
