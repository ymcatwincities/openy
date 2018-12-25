<?php

namespace Drupal\plugin\DefaultPluginResolver;

use Drupal\plugin\Event\PluginEvents;
use Drupal\plugin\Event\ResolveDefaultPlugin;
use Drupal\plugin\PluginType\PluginTypeInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provides an event-based default plugin resolver.
 */
class EventBasedDefaultPluginResolver implements DefaultPluginResolverInterface {

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Creates a new instance.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   */
  public function __construct(EventDispatcherInterface $event_dispatcher) {
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public function createDefaultPluginInstance(PluginTypeInterface $plugin_type) {
    $event = new ResolveDefaultPlugin($plugin_type);
    $this->eventDispatcher->dispatch(PluginEvents::RESOLVE_DEFAULT_PLUGIN, $event);

    return $event->getDefaultPluginInstance();
  }

}
