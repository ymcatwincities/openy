<?php

namespace Drupal\plugin\Event;

/**
 * Defines Plugin events.
 */
final class PluginEvents {

  /**
   * The name of the event that is fired when a default plugin instance is
   * resolved.
   *
   * @see \Drupal\plugin\Event\ResolveDefaultPlugin
   */
  const RESOLVE_DEFAULT_PLUGIN = 'drupal.plugin.resolve_default_plugin';
}
