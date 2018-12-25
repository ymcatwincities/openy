<?php

namespace Drupal\fullcalendar\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Plugin type manager for FullCalendar plugins.
 */
class FullcalendarManager extends DefaultPluginManager {

  /**
   * @todo.
   */
  protected $defaults = array(
    'css' => FALSE,
    'js' => FALSE,
  );

  /**
   * Constructs a FullcalendarManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/fullcalendar/type', $namespaces, $module_handler, 'Drupal\fullcalendar\Plugin\FullcalendarInterface', 'Drupal\fullcalendar\Annotation\FullcalendarOption');
  }

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\fullcalendar\Plugin\FullcalendarInterface
   */
  public function createInstance($plugin_id, array $configuration = array(), $style = NULL) {
    $plugin = parent::createInstance($plugin_id, $configuration);
    if($style) {
      $plugin->setStyle($style);
    }
    return $plugin;
  }

}
