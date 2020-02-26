<?php

namespace Drupal\paragraph_skins;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\Discovery\ContainerDerivativeDiscoveryDecorator;
use Drupal\Core\Plugin\Discovery\YamlDiscovery;


/**
 * Manages discovery and instantiation of skin group plugins.
 *
 * @see \Drupal\state_machine\Plugin\WorkflowGroup\WorkflowGroupInterface
 * @see plugin_api
 */
class SkinGroupManager extends DefaultPluginManager implements PluginManagerInterface {

  /**
   * Default values for each workflow_group plugin.
   *
   * @var array
   */
  protected $defaults = [
    'label' => '',
  ];

  /**
   * Constructs a new ParagraphSkins object.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   */
  public function __construct(ModuleHandlerInterface $module_handler, CacheBackendInterface $cache_backend) {
    $this->moduleHandler = $module_handler;
    $this->setCacheBackend($cache_backend, 'paragraph_skin_groups', ['paragraph_skin_groups']);
    $this->alterInfo('paragraph_skin_groups');
  }

  /**
   * {@inheritdoc}
   */
  protected function getDiscovery() {
    if (!isset($this->discovery)) {
      $this->discovery = new YamlDiscovery('paragraph_skin_groups', $this->moduleHandler->getModuleDirectories());
      $this->discovery = new ContainerDerivativeDiscoveryDecorator($this->discovery);
    }
    return $this->discovery;
  }

  /**
   * {@inheritdoc}
   */
  public function processDefinition(&$definition, $plugin_id) {
    parent::processDefinition($definition, $plugin_id);
  }

}
