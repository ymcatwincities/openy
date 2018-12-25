<?php

namespace Drupal\panels\Storage;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\panels\Annotation\PanelsStorage;
use Drupal\panels\Plugin\DisplayVariant\PanelsDisplayVariant;

/**
 * Panels storage manager service.
 */
class PanelsStorageManager extends DefaultPluginManager implements PanelsStorageManagerInterface {

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a PanelsStorageManager.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user service.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler, AccountProxyInterface $current_user) {
    parent::__construct('Plugin/PanelsStorage', $namespaces, $module_handler, PanelsStorageInterface::class, PanelsStorage::class);

    $this->currentUser = $current_user;

    $this->alterInfo('panels_storage_info');
    $this->setCacheBackend($cache_backend, 'panels_storage');
  }

  /**
   * An associative array of Panels storages services keyed by storage type.
   *
   * @var \Drupal\panels\Storage\PanelsStorageInterface[]
   */
  protected $storage = [];

  /**
   * Gets a storage plugin.
   *
   * @param string $storage_type
   *   The storage type used by the storage plugin.
   *
   * @return \Drupal\panels\Storage\PanelsStorageInterface
   *   The Panels storage plugin with the given storage type.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *   If there is no Panels storage plugin with the given storage type.
   */
  protected function getStorage($storage_type) {
    if (!isset($this->storage[$storage_type])) {
      $this->storage[$storage_type] = $this->createInstance($storage_type);
    }
    return $this->storage[$storage_type];
  }

  /**
   * {@inheritdoc}
   */
  public function load($storage_type, $id) {
    $storage = $this->getStorage($storage_type);
    return $storage->load($id);
  }

  /**
   * {@inheritdoc}
   */
  public function save(PanelsDisplayVariant $panels_display) {
    $storage = $this->getStorage($panels_display->getStorageType());
    $storage->save($panels_display);
  }

  /**
   * {@inheritdoc}
   */
  public function access($storage_type, $id, $op, AccountInterface $account = NULL) {
    if ($account === NULL) {
      $account = $this->currentUser->getAccount();
    }
    return $this->getStorage($storage_type)->access($id, $op, $account);
  }

}
