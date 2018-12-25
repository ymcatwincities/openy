<?php

namespace Drupal\plugin\Plugin\views\filter;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\plugin\PluginDefinition\PluginLabelDefinitionInterface;
use Drupal\plugin\PluginType\PluginTypeInterface;
use Drupal\views\Plugin\views\filter\InOperator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Views filter for plugin IDs.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("plugin_id")
 */
class PluginId extends InOperator implements ContainerFactoryPluginInterface {

  /**
   * The plugin type.
   *
   * @var \Drupal\plugin\PluginType\PluginTypeInterface
   */
  protected $pluginType;

  /**
   * Constructs a new instance.
   *
   * @param mixed[] $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed[] $plugin_definition
   *   The plugin definition.
   * @param \Drupal\plugin\PluginType\PluginTypeInterface $plugin_type
   *   The plugin type.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, PluginTypeInterface $plugin_type) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->pluginType = $plugin_type;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    /** @var \Drupal\plugin\PluginType\PluginTypeManagerInterface $plugin_type_manager */
    $plugin_type_manager = $container->get('plugin.plugin_type_manager');

    return new static($configuration, $plugin_id, $plugin_definition, $plugin_type_manager->getPluginType($configuration['plugin_type_id']));
  }

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {
    if (!is_null($this->valueOptions)) {
      return $this->valueOptions;
    }

    $this->valueTitle = (string) $this->pluginType->getLabel();
    $this->valueOptions = array_reduce($this->pluginType->getPluginManager()->getDefinitions(), function(array $value_options, $plugin_definition) {
      $plugin_definition = $this->pluginType->ensureTypedPluginDefinition($plugin_definition);
      $value_options[$plugin_definition->getId()] = $plugin_definition instanceof PluginLabelDefinitionInterface ? $plugin_definition->getLabel() : $plugin_definition->getId();
      return $value_options;
    }, []);
    natcasesort($this->valueOptions);

    return $this->valueOptions;
  }

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    parent::valueForm($form, $form_state);
    // Apply cacheability metadata, because the parent class does not.
    $this->getCacheableMetadata()->applyTo($form);

    return $form;
  }

  /**
   * Gets this instance's cacheable metadata.
   *
   * @return \Drupal\Core\Cache\CacheableMetadata
   */
  protected function getCacheableMetadata() {
    $cacheable_metadata = new CacheableMetadata();
    $cacheable_metadata->addCacheableDependency($this->pluginType->getPluginManager());
    $cacheable_metadata->addCacheTags(parent::getCacheTags());
    $cacheable_metadata->addCacheContexts(parent::getCacheContexts());
    $cacheable_metadata->mergeCacheMaxAge(parent::getCacheMaxAge());

    return $cacheable_metadata;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheTags() {
    return $this->getCacheableMetadata()->getCacheTags();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return $this->getCacheableMetadata()->getCacheContexts();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return $this->getCacheableMetadata()->getCacheMaxAge();
  }

}
