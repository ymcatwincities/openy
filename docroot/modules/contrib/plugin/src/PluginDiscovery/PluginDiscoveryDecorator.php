<?php

namespace Drupal\plugin\PluginDiscovery;

use Drupal\Component\Plugin\Discovery\CachedDiscoveryInterface;
use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\Component\Plugin\Discovery\DiscoveryTrait;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;

/**
 * Decorates plugin discovery.
 */
class PluginDiscoveryDecorator implements DiscoveryInterface, CachedDiscoveryInterface {

  use DependencySerializationTrait;
  use DiscoveryTrait;

  /**
   * The decorated discovery.
   *
   * @var \Drupal\Component\Plugin\Discovery\DiscoveryInterface
   */
  protected $decoratedDiscovery;

  /**
   * The processed plugin definitions.
   *
   * @var array[]|null
   *   An array with plugin definitions or NULL if the definitions have not been
   *   loaded yet.
   *
   * @see self::getDefinitions()
   */
  protected $pluginDefinitions;

  /**
   * Whether or not to use plugin caching.
   *
   * @var bool
   */
  protected $useCaches = TRUE;

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\Component\Plugin\Discovery\DiscoveryInterface $decorated_discovery
   *   The decorated discovery.
   */
  public function __construct(DiscoveryInterface $decorated_discovery) {
    $this->decoratedDiscovery = $decorated_discovery;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitions() {
    if (is_null($this->pluginDefinitions) || !$this->useCaches) {
      $this->pluginDefinitions = $this->processDecoratedDefinitions($this->decoratedDiscovery->getDefinitions());
    }

    return $this->pluginDefinitions;
  }

  /**
   * Processes the definitions from the decorated discovery.
   *
   * Any changes to the decorated definitions should be performed here.
   *
   * @param mixed[] $decorated_definitions
   *   The decorated plugin definitions, keyed by plugin ID.
   *
   * @return mixed[]
   *   The processed plugin definitions.
   */
  protected function processDecoratedDefinitions(array $decorated_definitions) {
    return $decorated_definitions;
  }

  /**
   * {@inheritdoc}
   */
  public function useCaches($use_caches = FALSE) {
    $this->useCaches = $use_caches;
    $decorated_discovery = $this->decoratedDiscovery;
    if ($decorated_discovery instanceof CachedDiscoveryInterface) {
      $decorated_discovery->useCaches($use_caches);
    }
    $this->clearCachedDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public function clearCachedDefinitions() {
    $this->pluginDefinitions = NULL;
    $decorated_discovery = $this->decoratedDiscovery;
    if ($decorated_discovery instanceof CachedDiscoveryInterface) {
      $decorated_discovery->clearCachedDefinitions();
    }
  }

}
