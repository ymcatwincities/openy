<?php

namespace Drupal\purge\Plugin\Purge\Queuer;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Component\Plugin\PluginManagerInterface;
use Drupal\purge\Plugin\Purge\Queuer\QueuersServiceInterface;
use Drupal\purge\IteratingServiceBaseTrait;
use Drupal\purge\ModifiableServiceBaseTrait;
use Drupal\purge\ServiceBase;

/**
 * Provides a service that provides access to loaded queuers.
 */
class QueuersService extends ServiceBase implements QueuersServiceInterface {
  use IteratingServiceBaseTrait;
  use ModifiableServiceBaseTrait;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The plugin manager for queuers.
   *
   * @var \Drupal\purge\Plugin\Purge\Queuer\PluginManager
   */
  protected $pluginManager;

  /**
   * Construct \Drupal\purge\Plugin\Purge\Processor\ProcessorsService.
   *
   * @param \Drupal\Component\Plugin\PluginManagerInterface $pluginManager
   *   The plugin manager for this service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   */
  public function __construct(PluginManagerInterface $pluginManager, ConfigFactoryInterface $config_factory) {
    $this->pluginManager = $pluginManager;
    $this->configFactory = $config_factory;
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
  public function get($plugin_id) {
    $this->initializePluginInstances();
    foreach ($this as $queuer) {
      if ($queuer->getPluginId() === $plugin_id) {
        return $queuer;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginsEnabled() {
    if (is_null($this->plugins_enabled)) {

      // Build a mapping of all plugins and whether they are enabled by default.
      $this->plugins_enabled = [];
      foreach ($this->getPlugins() as $plugin_id => $definition) {
        $enable_by_default = ($definition['enable_by_default'] === TRUE);
        $this->plugins_enabled[$plugin_id] = $enable_by_default;
      }

      // Override the mapping with information stored in CMI, then filter out
      // everything that isn't enabled and finally flip the array with just ids.
      $queuers = $this->configFactory->get('purge.plugins')->get('queuers');
      if (!is_null($queuers)) {
        foreach ($queuers as $inst) {
          if (isset($this->plugins_enabled[$inst['plugin_id']])) {
            $this->plugins_enabled[$inst['plugin_id']] = $inst['status'];
          }
        }
      }
      foreach ($this->plugins_enabled as $plugin_id => $status) {
        if (!$status) {
          unset($this->plugins_enabled[$plugin_id]);
        }
      }
      $this->plugins_enabled = array_keys($this->plugins_enabled);
    }
    return $this->plugins_enabled;
  }

  /**
   * {@inheritdoc}
   */
  public function reload() {
    parent::reload();
    // Without this, the tests will throw "failed to instantiate user-supplied
    // statement class: CREATE TABLE {cache_config}".
    $this->configFactory = \Drupal::configFactory();
    $this->reloadIterator();
  }

  /**
   * {@inheritdoc}
   */
  public function setPluginsEnabled(array $plugin_ids) {
    $definitions = $this->pluginManager->getDefinitions();

    // Gather all plugins mentioned in CMI and those available right now, set
    // them disabled first. Then flip the switch for given plugin_ids.
    $setting_assoc = [];
    $instances = $this->configFactory->get('purge.plugins')->get('queuers');
    if (!is_null($instances)) {
      foreach ($instances as $inst) {
        $setting_assoc[$inst['plugin_id']] = FALSE;
      }
    }
    foreach ($definitions as $definition) {
      $setting_assoc[$definition['id']] = FALSE;
    }
    foreach ($plugin_ids as $plugin_id) {
      if (!isset($definitions[$plugin_id])) {
        throw new \LogicException('Invalid plugin_id.');
      }
      $setting_assoc[$plugin_id] = TRUE;
    }

    // Convert the array to the CMI storage format and commit.
    $setting = [];
    foreach ($setting_assoc as $plugin_id => $status) {
      $setting[] = [
        'plugin_id' => $plugin_id,
        'status' => $status,
      ];
    }
    $this->configFactory
      ->getEditable('purge.plugins')
      ->set('queuers', $setting)
      ->save();
    $this->reload();
  }

}
