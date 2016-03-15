<?php

/**
 * @file
 * Contains \Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticsService.
 */

namespace Drupal\purge\Plugin\Purge\DiagnosticCheck;

use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\purge\ServiceBase;
use Drupal\purge\IteratingServiceBaseTrait;
use Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticsServiceInterface;
use Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticCheckInterface;

/**
 * Provides a service that interacts with diagnostic checks.
 */
class DiagnosticsService extends ServiceBase implements DiagnosticsServiceInterface {
  use ContainerAwareTrait;
  use IteratingServiceBaseTrait;

  /**
   * The plugin manager for checks.
   *
   * @var \Drupal\purge\Plugin\Purge\DiagnosticCheck\PluginManager
   */
  protected $pluginManager;

  /**
   * The purge executive service, which wipes content from external caches.
   *
   * Do not access this property directly, use ::getPurgers.
   *
   * @var \Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface
   */
  private $purgePurgers;

  /**
   * The queue in which to store, claim and release invalidation objects from.
   *
   * Do not access this property directly, use ::getQueue.
   *
   * @var \Drupal\purge\Plugin\Purge\Queue\QueueServiceInterface
   */
  private $purgeQueue;

  /**
   * Construct \Drupal\purge\Plugin\Purge\DiagnosticCheck\DiagnosticsService.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $pluginManager
   *   The plugin manager for this service.
   */
  function __construct(PluginManagerInterface $pluginManager) {
    $this->pluginManager = $pluginManager;
  }

  /**
   * {@inheritdoc}
   * @ingroup countable
   */
  public function count() {
    $this->initializePluginInstances();
    return count($this->instances);
  }

  /**
   * {@inheritdoc}
   */
  public function getHookRequirementsArray() {
    $this->initializePluginInstances();
    $requirements = [];
    foreach ($this as $check) {
      $requirements[$check->getPluginId()] = $check->getHookRequirementsArray();
    }
    return $requirements;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginsEnabled() {
    if (!is_null($this->plugins_enabled)) {
      return $this->plugins_enabled;
    }

    // We blindly load all diagnostic check plugins that we discovered, but not
    // when plugins put dependencies on either a queue or purger plugin. When
    // plugins do depend, we load 'purge.purgers' and/or 'purge.queue' and
    // carefully check if we should load them or not.
    $load = function($needles, $haystack) {
      if (empty($needles)) return TRUE;
      foreach ($needles as $needle) {
        if (in_array($needle, $haystack)) {
          return TRUE;
        }
      }
      return FALSE;
    };
    $this->plugins_enabled = [];
    foreach ($this->getPlugins() as $plugin) {
      if (!empty($plugin['dependent_queue_plugins'])) {
        if (!$load($plugin['dependent_queue_plugins'], $this->getQueue()->getPluginsEnabled())) {
          continue;
        }
      }
      if (!empty($plugin['dependent_purger_plugins'])) {
        if (!$load($plugin['dependent_purger_plugins'], $this->getPurgers()->getPluginsEnabled())) {
          continue;
        }
      }
      $this->plugins_enabled[] = $plugin['id'];
    }
    return $this->plugins_enabled;
  }

  /**
   * Retrieve the 'purge.purgers' service - lazy loaded.
   *
   * @return \Drupal\purge\Plugin\Purge\Purger\PurgersServiceInterface
   */
  protected function getPurgers() {
    if (is_null($this->purgePurgers)) {
      $this->purgePurgers = $this->container->get('purge.purgers');
    }
    return $this->purgePurgers;
  }

  /**
   * {@inheritdoc}
   */
  public function getRequirementsArray() {
    $this->initializePluginInstances();
    $requirements = [];
    foreach ($this as $check) {
      $requirements[$check->getPluginId()] = $check->getRequirementsArray();
    }
    return $requirements;
  }

  /**
   * Retrieve the 'purge.queue' service - lazy loaded.
   *
   * @return \Drupal\purge\Plugin\Purge\Queue\QueueServiceInterface
   */
  protected function getQueue() {
    if (is_null($this->purgeQueue)) {
      $this->purgeQueue = $this->container->get('purge.queue');
    }
    return $this->purgeQueue;
  }

  /**
   * {@inheritdoc}
   */
  public function isSystemOnFire() {
    $this->initializePluginInstances();
    foreach ($this as $check) {
      if ($check->getSeverity() === DiagnosticCheckInterface::SEVERITY_ERROR) {
        return $check;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function isSystemShowingSmoke() {
    $this->initializePluginInstances();
    foreach ($this as $check) {
      if ($check->getSeverity() === DiagnosticCheckInterface::SEVERITY_WARNING) {
        return $check;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function reload() {
    parent::reload();
    $this->reloadIterator();
    $this->purgePurgers = NULL;
    $this->purgeQueue = NULL;
  }

}
