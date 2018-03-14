<?php

/**
 * @file
 * Contains \Drupal\video\ProviderManager.
 */

namespace Drupal\video;

use Drupal\Component\Plugin\Mapper\MapperInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Gathers the provider plugins.
 */
class ProviderManager extends DefaultPluginManager implements ProviderManagerInterface, MapperInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/video/Provider', $namespaces, $module_handler, 'Drupal\video\ProviderPluginInterface', 'Drupal\video\Annotation\VideoEmbeddableProvider');
  }

  /**
   * {@inheritdoc}
   */
  public function getProvidersOptionList() {
    $options = [];
    foreach ($this->getDefinitions() as $definition) {
      $options[$definition['id']] = $definition['label'];
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function loadDefinitionsFromOptionList($options) {
    $definitions = [];
    // When no options are selected, all plugins are applicable.
    if (count(array_keys($options, '0')) == count($options) || empty($options)) {
      return $this->getDefinitions();
    }
    else {
      foreach ($options as $provider_id => $enabled) {
        if ($enabled) {
          $definitions[$provider_id] = $this->getDefinition($provider_id);
        }
      }
    }
    return $definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function loadApplicableDefinitionMatches(array $definitions, $user_input) {
    foreach ($definitions as $definition) {
      foreach($definition['regular_expressions'] as $reqular_expr){
        if (preg_match($reqular_expr, $user_input, $matches)) {
          return array('definition' => $definition, 'matches' => $matches);
        }
      }
    }
    return FALSE;
  }
  
  /**
   * {@inheritdoc}
   */
  public function loadProviderFromStream($stream, $file, $metadata = array(), $settings = array()) {
    $definitions = $this->getDefinitions();
    foreach ($definitions as $definition) {
      if($definition['stream_wrapper'] == $stream){
        return $definition ? $this->createInstance($definition['id'], ['file' => $file, 'metadata' => $metadata, 'settings' => $settings]) : FALSE;
      }
    }
  }
}
