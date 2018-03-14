<?php

namespace Drupal\panels\Plugin\PanelsPattern;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\Url;
use Drupal\ctools\ContextMapperInterface;
use Drupal\panels\CachedValuesGetterTrait;
use Drupal\user\SharedTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @PanelsPattern("default")
 */
class DefaultPattern extends PluginBase implements PanelsPatternInterface, ContainerFactoryPluginInterface {

  use CachedValuesGetterTrait;

  /**
   * The context mapper.
   *
   * @var \Drupal\ctools\ContextMapperInterface
   */
  protected $contextMapper;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('ctools.context_mapper'));
  }

  /**
   * DefaultPattern constructor.
   *
   * @param array $configuration
   *   The plugin's configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\ctools\ContextMapperInterface $context_mapper
   *   The context mapper.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ContextMapperInterface $context_mapper) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->contextMapper = $context_mapper;
  }

  /**
   * {@inheritdoc}
   */
  public function getMachineName($cached_values) {
    // PageManager needs special handling, so lets see if we're dealing with a PM page.
    if (isset($cached_values['page_variant'])) {
      return implode('--', [$cached_values['id'], $cached_values['page_variant']->id()]);
    }
    return $cached_values['id'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultContexts(SharedTempStoreFactory $tempstore, $tempstore_id, $machine_name) {
    $cached_values = $this->getCachedValues($tempstore, $tempstore_id, $machine_name);
    // PageManager specific context loading.
    if (!empty($cached_values['page_variant'])) {
      /** @var \Drupal\page_manager\PageVariantInterface $page_variant */
      $page_variant = $cached_values['page_variant'];
      return $page_variant->getContexts();
    }
    // General handling for contexts.
    return !empty($cached_values['contexts']) ? $this->contextMapper->getContextValues($cached_values['contexts']) : [];
  }

  /**
   * {@inheritdoc}
   */
  public function getBlockListUrl($tempstore_id, $machine_name, $region = NULL, $destination = NULL) {
    return Url::fromRoute('panels.select_block', [
      'tempstore_id' => $tempstore_id,
      'machine_name' => $machine_name,
      'region' => $region,
      'destination' => $destination,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getBlockAddUrl($tempstore_id, $machine_name, $block_id, $region = NULL, $destination = NULL) {
    return Url::fromRoute('panels.add_block', [
      'tempstore_id' => $tempstore_id,
      'machine_name' => $machine_name,
      'block_id' => $block_id,
      'region' => $region,
      'destination' => $destination,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getBlockEditUrl($tempstore_id, $machine_name, $block_id, $destination = NULL) {
    return Url::fromRoute('panels.edit_block', [
      'tempstore_id' => $tempstore_id,
      'machine_name' => $machine_name,
      'block_id' => $block_id,
      'destination' => $destination,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getBlockDeleteUrl($tempstore_id, $machine_name, $block_id, $destination = NULL) {
    return Url::fromRoute('panels.delete_block', [
      'tempstore_id' => $tempstore_id,
      'machine_name' => $machine_name,
      'block_id' => $block_id,
      'destination' => $destination,
    ]);
  }

}
